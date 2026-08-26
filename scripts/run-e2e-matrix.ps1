# Runs the complete 3-project Playwright matrix with per-project database
# isolation. The visual baselines encode the PRISTINE seeded state (no E2E
# orders, no cart rows, full variant stock), so within each project they run
# FIRST from a freshly cleaned database; the functional suites that create
# orders/cart rows run afterwards. A single sequential "npx playwright test"
# would both accumulate orders across projects and let cart-checkout mutate
# state before visual-baselines renders the dashboards, failing baselines
# spuriously.
#
# Usage: powershell -ExecutionPolicy Bypass -File scripts\run-e2e-matrix.ps1
#        (-UpdateSnapshots passes --update-snapshots through to Playwright.)

param(
    [switch]$UpdateSnapshots
)

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot

$extra = @()
if ($UpdateSnapshots) { $extra += '--update-snapshots' }

# Every spec EXCEPT visual-baselines: these may create orders, carts,
# accounts and wishlists.
$functionalSpecs = @(
    'e2e/accessibility.spec.ts',
    'e2e/auth.spec.ts',
    'e2e/cart-checkout.spec.ts',
    'e2e/catalogue.spec.ts',
    'e2e/mobile-nav.spec.ts',
    'e2e/product-detail.spec.ts',
    'e2e/seo.spec.ts'
)

function Invoke-Step {
    param([string]$Phase, [string[]]$PlaywrightArgs)
    Write-Host "===== $Phase ====="
    npx playwright test @PlaywrightArgs
    if ($LASTEXITCODE -ne 0) { throw "Playwright failed during: $Phase" }
}

foreach ($project in @('desktop', 'mobile', 'tablet')) {
    Write-Host ""
    Write-Host "===== Matrix project: $project ====="

    Write-Host "----- resetting E2E state (visual phase) -----"
    php "$repo\storage\cleanup-e2e.php"
    if ($LASTEXITCODE -ne 0) { throw "cleanup-e2e.php failed before $project" }

    # Visual baselines always see the pristine seeded database.
    Invoke-Step "$project : visual baselines (clean DB)" (@('--project=' + $project) + $extra + @('e2e/visual-baselines.spec.ts'))

    Write-Host "----- resetting E2E state (functional phase) -----"
    php "$repo\storage\cleanup-e2e.php"
    if ($LASTEXITCODE -ne 0) { throw "cleanup-e2e.php failed mid-$project" }

    Invoke-Step "$project : functional suites" (@('--project=' + $project) + $functionalSpecs)
}

Write-Host ""
Write-Host "===== Matrix complete - final E2E state cleanup ====="
php "$repo\storage\cleanup-e2e.php"
if ($LASTEXITCODE -ne 0) { throw 'final cleanup-e2e.php failed' }
Write-Host 'Matrix PASS (all projects green, database clean).'
