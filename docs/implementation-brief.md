# Sordar Agro: Website Redesign, Engineering Audit & Claude Implementation Brief

## Purpose

Use this document as the implementation brief for Claude Code/Claude AI.

The goal is to transform the current Sordar Agro project into a polished, modern, trustworthy, fast, accessible, highly testable aquarium marketplace without unnecessarily rewriting the existing Laravel application.

The intended result is **not a copy of Believers**. Believers is a benchmark for useful ecommerce patterns. The final product should have its own identity and should ideally be better in aquarium-specific usability, clarity, product discovery, and polish.

---

# 1. Source Material

Primary sources reviewed:

1. GitHub repository:
   `https://github.com/NafisAfzal/Sordar-Agro`

2. Current live website:
   `https://web-production-5a66.up.railway.app/`

3. Benchmark/reference:
   `https://believers.com.bd/`

The original audit brief requires inspection beyond the homepage, including navigation, product listing, product detail, search, filters, sorting, categories, cart, checkout, authentication, accounts, responsive behavior, forms, loading/error/empty states, accessibility, SEO, performance, architecture, API/data fetching, database, authentication/security, deployment, and testing.

---

# 2. Most Important Instruction

**Do not rebuild the application from scratch.**

Preserve the existing:

- Laravel backend
- Blade storefront architecture
- Eloquent models
- routes
- authentication
- authorization
- seller/admin functionality
- inventory logic
- checkout logic
- existing business rules
- existing tests

Refactor only where there is a clear engineering reason.

The primary goal is to improve the storefront UX/UI, design system, responsiveness, accessibility, SEO, performance, maintainability, and testing coverage.

Do not migrate to React/Next.js merely for visual improvements.

---

# 3. Current Assessment

Overall assessment:

| Area | Approximate assessment | Priority |
|---|---:|---|
| Core backend architecture | 7.5/10 | Medium |
| Commerce logic | 7.5/10 | High |
| Authentication/authorization | 7/10 | High |
| Existing test foundation | 7.5/10 | Very high |
| Current visual design | 4.5/10 | Very high |
| Homepage merchandising | 4/10 | Very high |
| Product discovery | 5.5/10 | Very high |
| Product detail UX | 5.5/10 | High |
| Mobile storefront | 5/10 | Very high |
| Accessibility | 5.5/10 | High |
| SEO | 4.5/10 | High |
| Performance architecture | 6/10 | High |
| Deployment setup | 6/10 | Very high |

The biggest conclusion:

> The backend foundation is reasonably strong. The storefront presentation and product experience need the largest improvement.

---

# 4. What Is Already Good

Preserve these strengths.

## 4.1 Domain model

The project models important commerce concepts including:

- customers
- sellers
- administrators
- products
- product variants
- categories
- carts
- wishlists
- orders
- care guides
- community content
- inventory

The product model supports aquarium-specific information such as:

- tank size
- temperament
- variants
- stock

This is a strong foundation.

## 4.2 Route and role separation

The project separates:

- public storefront
- authentication
- shopping
- seller workspace
- admin workspace

Keep this structure.

## 4.3 Inventory protection

The checkout/payment logic uses:

- database transactions
- stock validation
- `lockForUpdate()`

to reduce concurrent overselling.

Do not replace this with weaker client-side-only logic.

## 4.4 Authorization

Role middleware and authorization tests already exist.

Preserve server-side authorization. Never trust frontend role controls.

## 4.5 Authentication

The existing login flow regenerates the session and handles inactive users. Registration prevents users from simply assigning themselves privileged roles.

Preserve this behavior.

## 4.6 Existing testing foundation

There are already:

- Unit tests
- Feature tests
- authentication tests
- authorization tests
- inventory tests
- shop-flow tests
- seller tests
- admin tests
- wishlist tests
- community tests
- care-guide tests

The project already has a useful testing foundation.

The major missing layer is browser-level E2E and visual regression testing.

---

# 5. Main UX Problem

The current frontend feels too much like a generic Bootstrap/student ecommerce template.

Common visual characteristics that should be reduced:

- heavy gradients
- generic rounded Bootstrap cards
- generic shadows
- oversized decorative icons
- flat visual hierarchy
- excessive visual emphasis on UI containers
- weak product merchandising
- weak photography
- insufficient homepage storytelling

The product should instead feel:

- natural
- aquatic
- calm
- modern
- premium
- trustworthy
- practical
- easygoing
- structured
- comfortable on mobile

Avoid unnecessary visual complexity.

---

# 6. New Design Philosophy

Sordar Agro should feel like a **specialist aquarium brand**, not a generic ecommerce template.

Think:

> premium aquarium specialist + easy local shopping experience

Not:

> colorful Bootstrap marketplace

The design should make the user immediately understand:

1. What Sordar Agro sells.
2. Why the products can be trusted.
3. How to choose the right product.
4. How to buy quickly.

---

# 7. Benchmark: Believers

Believers should be used as a benchmark, not copied.

## Learn from Believers

Useful patterns include:

- strong product discoverability
- visible category organization
- clear commerce navigation
- dense but understandable product merchandising
- easy catalogue access
- prominent promotions
- clear pricing
- straightforward ecommerce structure
- persistent account/cart functions

## Do not copy

Do not copy:

- clothing-specific visual language
- exact colors
- exact layout
- exact components
- exact typography
- exact promotional style
- exact navigation taxonomy

Sordar Agro has a very different domain.

## Opportunity to be better

Sordar Agro should use aquarium-specific knowledge as part of shopping.

Examples:

- tank size
- temperament
- compatibility
- care difficulty
- feeding requirements
- plant suitability
- aquarium equipment compatibility

This can make product discovery significantly more useful than a generic ecommerce site.

---

# 8. Recommended Global Information Architecture

## Desktop primary navigation

Recommended:

```text
Shop
Fish
Plants
Food
Equipment
Care Guides
Community
```

Utility actions:

```text
Search
Wishlist
Cart
Account
```

Do not make every page/feature equal in importance.

Primary business journey:

```text
Shop
→ Category
→ Product
→ Cart
→ Checkout
```

Content such as Care Guides and Community should support the commerce experience rather than compete with it.

---

# 9. Header Redesign

Replace the current flat Bootstrap-style navigation with three conceptual layers.

## Layer 1: Announcement bar

Small and calm.

Example:

```text
Free delivery over ৳X | Healthy fish | Secure local payments
```

Only use claims that are actually true.

## Layer 2: Main header

Desktop:

```text
Logo | Search | Wishlist | Cart | Account
```

Search should be visually important.

Suggested placeholder:

```text
Search fish, plants, food, equipment...
```

## Layer 3: Category navigation

```text
Shop
Fish
Plants
Food
Equipment
Care
Community
```

## Mobile

Use:

- compact brand area
- search access
- cart access
- mobile menu drawer
- large search field
- clear category hierarchy

Do not simply rely on a generic Bootstrap collapse.

---

# 10. Homepage Redesign

The current homepage is too thin for a commercial storefront.

Recommended order:

## Section 1: Hero

Use real aquarium photography rather than a generic icon/gradient hero.

Suggested content:

```text
Build a healthier aquarium.

Healthy fish, aquatic plants, food and equipment,
selected for aquarium hobbyists in Bangladesh.

[ Shop Fish ]   [ Explore Equipment ]
```

Hero should use:

- strong photography
- clean negative space
- restrained text overlay
- high contrast
- responsive image handling

## Section 2: Shop by Category

Four clear visual tiles:

- Fish
- Plants
- Food
- Equipment

Use actual category/product imagery.

## Section 3: Featured Products

Show 4–8 products.

Cards should include:

- image
- category
- product name
- price or starting price
- availability
- variant indicator
- badges
- wishlist
- quick add

## Section 4: Aquarium Finder

This is a major differentiation opportunity.

Example:

```text
Find the right fish for your aquarium

Tank size
Budget
Temperament
Experience level

[ Show Suitable Fish ]
```

Use existing backend data such as tank size and temperament.

## Section 5: Aquarium Essentials

Promotional blocks:

- Food
- Lighting & Equipment
- Plants

## Section 6: Trust

Examples:

- Healthy livestock
- Clear tank suitability information
- Secure payment
- Delivery support
- Restock alerts
- Aquarium care guidance

Only display business claims that can be substantiated.

## Section 7: Care Guides

Improve visual design significantly.

## Section 8: Community

Feature useful community content without making the homepage feel like a forum.

## Section 9: Final CTA

```text
Ready to build your aquarium?

[ Shop the collection ]
```

---

# 11. Product Card Redesign

The product card should be rebuilt as a reusable design-system component.

Recommended structure:

```text
┌──────────────────────────────┐
│             Wishlist         │
│                              │
│          PRODUCT IMAGE       │
│                              │
│ NEW / LOW STOCK / BEST SELLER│
└──────────────────────────────┘

Category

Product Name

Short useful descriptor

৳450
or
From ৳450

Small / Medium / Large

[ Add to cart ]
```

Support:

- wishlist
- product badges
- variant indicator
- low-stock messaging
- secondary image on hover where useful
- quick add
- consistent image ratio

Do not use aggressive hover movement.

Use subtle transitions.

---

# 12. Product Listing / Discovery

The existing filters for:

- category
- price
- tank size
- temperament
- availability
- sorting

are useful.

Keep the business functionality.

Improve presentation.

## Desktop

Left sidebar:

```text
Category
Price
Tank size
Temperament
Difficulty
Availability
```

Main area:

```text
Fish
15 products

[Recommended]
```

Show active filter chips.

## Mobile

Use:

```text
[ Filter ] [ Sort ]
```

Then show filters in a bottom sheet/drawer.

Do not display a huge filter form permanently on narrow screens.

---

# 13. Search Redesign

The project already has product search/suggestions.

Improve this into a richer search experience.

## Search overlay

Before typing:

- recent searches
- popular searches
- categories

While typing:

- products
- categories
- care guides

Example:

```text
Search: betta

Products
  Premium Betta
  Half Moon Betta

Category
  Betta Fish

Guide
  How to Care for Betta Fish
```

Do not immediately introduce an external search engine unless catalogue scale justifies it.

---

# 14. Product Detail Redesign

The product page already has useful aquarium-specific information.

Preserve and improve it.

## Desktop

Two-column structure.

### Left

- main image
- thumbnails
- gallery
- zoom where useful

### Right

- category
- product name
- rating/reviews if available
- price
- stock
- variant selector
- quantity
- Add to cart
- Buy now
- Wishlist

### Below

Trust information:

- healthy livestock
- secure payment
- delivery support

Then information sections:

```text
Overview
Care
Compatibility
Shipping
Reviews
```

For fish, prioritize:

- minimum tank size
- temperament
- expected size
- care difficulty
- compatible tank mates
- feeding
- water requirements

This is a key opportunity for Sordar Agro to differentiate itself.

---

# 15. Cart

Improve visual clarity.

Show:

- product image
- product name
- variant
- quantity controls
- price
- remove
- subtotal
- shipping estimate if available
- total
- checkout CTA

On mobile, keep the final CTA easy to reach.

---

# 16. Checkout

The backend already uses server-side validation and transactional inventory protection.

Preserve that logic.

Improve the UX with a simple staged structure:

```text
1. Delivery
2. Payment
3. Review
```

Desktop:

```text
Left: customer/shipping/payment
Right: sticky order summary
```

Mobile:

- collapsible order summary
- large touch-friendly controls
- sticky final CTA where appropriate

Do not imply automatic payment verification if the current payment system only records/validates a user-entered transaction ID.

---

# 17. Payment Limitation

The current payment flow uses customer-provided bKash/Nagad transaction IDs rather than full gateway verification.

Therefore:

## Current UI should say something like

```text
Payment submitted.
We will verify your transaction.
```

Do not claim:

```text
Payment automatically verified
```

unless a real payment gateway integration has been implemented.

For a future production system, integrate an actual payment gateway rather than building around manual IDs.

---

# 18. Mobile-First Design

Treat mobile as a first-class product.

Test at minimum:

```text
360 × 800
390 × 844
412 × 915
768 × 1024
1024 × 1366
1440+ desktop
```

## Mobile header

Recommended:

```text
Logo   Search   Cart
```

followed by a large search field if appropriate.

## Product grid

Use two columns where product content remains readable.

## Product page

Large image first.

Keep purchase controls prominent.

Consider a sticky bottom action:

```text
[ Add to cart ] [ Buy now ]
```

Only use sticky controls where they do not obstruct content.

---

# 19. Design System

Create a real design system instead of modifying arbitrary Bootstrap classes indefinitely.

## Suggested color system

Primary:

```text
#0F4C5C
```

Secondary:

```text
#2C7A7B
```

Soft background:

```text
#F5F8F7
```

Surface:

```text
#FFFFFF
```

Main text:

```text
#162A2E
```

Muted text:

```text
#637579
```

Warm accent:

```text
#D8A84E
```

Use the accent sparingly.

Do not introduce many saturated colors.

## Typography

Use a modern, readable sans-serif system.

Possible direction:

```text
Plus Jakarta Sans
```

or

```text
Inter
```

Do not make every heading oversized.

Use a clear hierarchy.

## Spacing

Standardize around:

```text
4
8
12
16
24
32
48
64
80
```

## Container

Recommended desktop max width:

```text
1280px
```

Mobile padding:

```text
16px
```

Tablet:

```text
24px
```

Desktop:

```text
32px
```

## Radius

Recommended:

```text
Buttons: 10px
Inputs: 10px
Cards: 14px
Feature blocks: 20px
Pills: 999px
```

Reduce excessive roundness.

---

# 20. Frontend Architecture

Do not keep all UI patterns duplicated across Blade templates.

Gradually create reusable components such as:

```text
ProductCard
ProductPrice
ProductBadge
WishlistButton
CartButton
SearchBox
FilterPanel
SectionHeading
HeroBanner
CategoryCard
TrustBadge
```

Recommended structure:

```text
resources/
  views/
    components/
      commerce/
      navigation/
      product/
      forms/
      feedback/

    layouts/

    storefront/
    checkout/
    account/
    admin/
    seller/
```

Avoid abstraction for abstraction's sake.

Create reusable components where actual duplication exists.

---

# 21. Product Query Refactoring

The current product filtering logic is manageable but will become harder to maintain as filters grow.

Prefer readable query scopes or a dedicated filter service.

Example direction:

```php
Product::query()
    ->approved()
    ->search($search)
    ->category($category)
    ->priceBetween($minPrice, $maxPrice)
    ->tankSize($tankSize)
    ->temperament($temperament)
    ->inStock($inStock);
```

Do not sacrifice clarity for excessive architecture.

---

# 22. Database / Search Performance

Current-scale filtering is acceptable.

Prepare for growth.

Potential indexes:

- products.status
- products.category_id
- products.slug
- products.is_featured
- product_variants.product_id
- product_variants.stock
- product_variants.price
- categories.slug

If product search becomes large, consider:

- database full-text search
- Laravel Scout
- Meilisearch
- Typesense

Do not add external infrastructure before it is justified.

---

# 23. Image and Performance Strategy

Improve:

- image compression
- responsive image sizes
- WebP/AVIF where supported
- lazy-loading below-the-fold content
- eager-loading only the LCP/hero image
- explicit image dimensions
- caching
- efficient asset delivery

Avoid unnecessary JavaScript.

Reduce layout shift by reserving image dimensions.

---

# 24. SEO

Implement proper ecommerce SEO.

## Global

- unique title
- meta description
- canonical URL
- Open Graph
- social card metadata
- robots rules

## Product

Structured data for:

- product
- image
- price
- availability
- SKU
- brand where appropriate
- breadcrumb

## Category

Unique:

- title
- description
- canonical
- useful introductory text

## Technical

Implement:

```text
/sitemap.xml
/robots.txt
```

Ensure important product/category pages are indexable.

---

# 25. Accessibility

Perform a formal accessibility pass.

Check:

- semantic HTML
- keyboard navigation
- visible focus
- `:focus-visible`
- color contrast
- ARIA
- image alt text
- form labels
- error association
- touch target sizes
- screen-reader behavior
- reduced motion

Avoid hiding controls in ways that break keyboard users.

Any custom radio, filter, modal, drawer, dropdown, or mobile menu must be keyboard accessible.

Target touch sizes around 44px where practical.

---

# 26. Security

## High priority: demo credentials

The repository README contains demo credentials.

Production must not rely on default demo passwords.

Change the deployment process so:

- production credentials are unique
- demo accounts are disabled or removed
- no default admin password survives deployment
- admin creation is deliberate and secure

## Production environment

Production must explicitly use settings equivalent to:

```text
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
```

Do not copy local development environment configuration directly to production.

## Dependency security

The Composer configuration currently does not block insecure packages.

Add dependency security checks.

At minimum:

```bash
composer audit
```

should run as part of CI.

---

# 27. Railway / Deployment

The repository uses Docker/Railway.

Be careful with startup commands that combine deployment and database seeding.

Do not automatically seed production data every time the application starts.

Preferred operational flow:

```text
Build
→ Deploy
→ Run migrations deliberately
→ Seed only when explicitly intended
→ Start application
```

Keep application startup predictable.

Also verify:

- health checks
- logs
- environment variables
- database connectivity
- storage
- migrations
- rollback strategy
- static assets
- production APP_URL
- caching

---

# 28. Testing Strategy

Testing is a major priority.

Keep the current PHPUnit Unit + Feature structure.

Add Playwright for E2E.

Add screenshot regression.

Add accessibility checks.

---

# 29. Unit Tests

Cover:

- pricing
- variant calculations
- cart subtotal
- order total
- stock calculations
- validation
- formatting
- product utilities
- filtering logic
- payment transaction ID handling
- business rules

---

# 30. Feature Tests

Cover:

## Authentication

- registration
- login
- logout
- email verification
- password reset
- inactive accounts

## Commerce

- product browsing
- search
- filters
- sorting
- wishlist
- add to cart
- update quantity
- remove cart item
- checkout
- payment
- order history

## Authorization

- customer cannot access admin
- customer cannot access seller
- seller cannot perform admin-only actions
- users cannot access another user's orders

## Inventory

- out-of-stock
- variant stock changes
- concurrent stock handling
- product removed before checkout
- stock changed during checkout

---

# 31. Playwright E2E

Implement realistic user journeys.

## Main happy path

```text
Homepage
→ Shop
→ Search
→ Filter
→ Product
→ Select variant
→ Add to cart
→ Update quantity
→ Checkout
→ Submit payment
→ Order confirmation
```

## Additional flows

- login
- logout
- registration
- wishlist
- order history
- product search
- mobile navigation

---

# 32. Failure Scenarios

Test:

- product becomes unavailable
- invalid coupon if coupons are added
- invalid quantity
- API/server failure
- network failure
- expired session
- unauthorized URL
- invalid form data
- payment failure
- empty cart
- product deleted after adding to cart
- stock changed during checkout
- duplicate transaction ID

---

# 33. Visual Regression

Create baseline screenshots for:

```text
Homepage desktop
Homepage mobile
Catalogue desktop
Catalogue mobile
Product desktop
Product mobile
Cart
Checkout
Login
Registration
Seller dashboard
Admin dashboard
```

Use Playwright screenshot comparisons.

Every significant frontend change should be checked against visual regression.

---

# 34. Accessibility Testing

Use automated checks such as axe in Playwright.

Combine automated checks with manual keyboard testing.

Test at least:

- focus order
- menu access
- modal closing
- form errors
- search
- filter drawer
- cart controls
- checkout
- mobile navigation

---

# 35. Recommended Implementation Roadmap

## Phase 0 — Safety

### Change

- production environment
- demo credentials
- production seed behavior
- dependency audit
- deployment safety

### Why

Security and deployment safety must exist before major UI work.

### Priority

P0

### Risk

Low.

### Verification

- production debug disabled
- no default credentials
- composer audit passes
- deployment works without unwanted seeding

---

## Phase 1 — Design Foundation

### Change

Create:

- color tokens
- typography
- spacing
- radius
- buttons
- inputs
- cards
- badges
- focus states
- responsive rules

### Why

Prevents visual inconsistency.

### Priority

P0

### Verification

All new storefront components use design tokens rather than arbitrary values.

---

## Phase 2 — Header / Navigation

### Change

Implement:

- announcement bar
- primary header
- search
- category navigation
- mobile drawer
- wishlist
- cart
- account

### Why

Navigation is global and affects every page.

### Priority

P0

### Acceptance Criteria

- understandable within seconds
- responsive
- keyboard accessible
- search easy to reach
- cart always visible
- mobile navigation does not feel like desktop squeezed into mobile

---

## Phase 3 — Homepage

### Change

Implement the new homepage architecture.

### Why

Highest visual impact.

### Priority

P0

### Acceptance Criteria

The homepage should communicate:

- what Sordar Agro sells
- category structure
- featured products
- trust
- aquarium-specific expertise

within a very short scan.

---

## Phase 4 — Product Discovery

### Change

Upgrade:

- cards
- search
- category browsing
- filtering
- sorting
- mobile filter interface

### Priority

P0

### Acceptance Criteria

A new user can quickly find a suitable product without instructions.

---

## Phase 5 — Product Details

### Change

Upgrade:

- image gallery
- purchase panel
- variant selection
- care information
- compatibility
- trust
- related products
- review area where supported

### Priority

P1

---

## Phase 6 — Cart / Checkout

### Change

Upgrade UX while preserving backend safety.

### Priority

P1

---

## Phase 7 — Mobile

### Change

Full mobile UX pass.

### Priority

P0

---

## Phase 8 — Performance / SEO / Accessibility

### Change

Implement technical improvements.

### Priority

P1

---

## Phase 9 — Testing

### Change

Implement:

- Playwright
- E2E
- screenshots
- accessibility
- regression coverage

### Priority

P0

---

## Phase 10 — Final Polish

Only after core UX is correct:

- subtle animations
- micro-interactions
- hover states
- loading states
- skeleton states
- refined transitions
- edge-case polish

### Priority

P2

---

# 36. Quick Wins

Implement these early:

1. Remove heavy gradients.
2. Replace generic category icons with real imagery.
3. Redesign product cards.
4. Build a photography-led hero.
5. Make search prominent.
6. Add quick add to product cards.
7. Improve image ratios.
8. Improve empty/loading/error states.
9. Improve mobile purchase controls.
10. Add trust messaging.

---

# 37. Things NOT to Do

Do not:

- rewrite the backend unnecessarily
- migrate to React only for aesthetics
- introduce microservices
- add Redux without a real need
- add Elasticsearch immediately
- introduce large UI libraries unnecessarily
- copy Believers pixel-for-pixel
- use excessive animations
- create huge navigation menus
- hardcode products into the homepage
- scatter arbitrary style values throughout templates

---

# 38. Claude Code Working Rules

When implementing:

## Rule 1 — Inspect before editing

Before modifying a file:

1. Read the existing implementation.
2. Determine dependencies.
3. Identify related tests.
4. Check routes/controllers/models involved.
5. Then modify.

Do not blindly replace files.

## Rule 2 — Preserve behavior

A visual redesign must not silently remove:

- search
- filtering
- sorting
- wishlist
- cart
- variants
- stock validation
- checkout
- role protection
- admin/seller functionality

## Rule 3 — Small coherent changes

Prefer multiple logical commits/phases over one huge rewrite.

## Rule 4 — Test after each major subsystem

At minimum run appropriate:

```bash
php artisan test
```

and relevant browser tests.

## Rule 5 — Do not hide bugs

If a redesign exposes an existing backend problem:

- identify it
- fix it if safe
- add a regression test
- document the change

## Rule 6 — Do not invent business claims

Do not add claims such as:

- guaranteed healthy fish
- same-day delivery
- free delivery
- verified payments
- lifetime guarantees

unless the real business logic supports them.

---

# 39. Implementation Instructions for Major Features

## Header

### Existing problem

The current header places too many functions into a single Bootstrap-style navigation layer and lacks strong hierarchy.

### Desired behavior

Create a clear global navigation system with separate brand, search, utility and category areas.

### Component structure

Suggested:

```text
AnnouncementBar
MainHeader
SearchBox
DesktopCategoryNav
MobileMenu
WishlistButton
CartButton
AccountMenu
```

### Responsive behavior

Desktop:

- full category navigation

Mobile:

- menu drawer
- persistent search/cart access

### Accessibility

- semantic navigation
- keyboard access
- visible focus
- ARIA only where actually necessary
- Escape closes overlays/drawers

### Testing

- desktop nav
- mobile nav
- keyboard
- search
- cart
- account
- visual regression

---

## Homepage

### Existing problem

The current homepage is visually generic and lacks enough merchandising structure.

### Desired behavior

Make the homepage a commerce + aquarium expertise landing page.

### Components

```text
HeroBanner
CategoryGrid
FeaturedProducts
AquariumFinder
PromoCards
TrustSection
CareGuideSection
CommunitySection
FinalCTA
```

### Data

Use actual database-driven products/categories/content.

### Responsive

Do not merely stack desktop sections.

Design mobile compositions intentionally.

### Acceptance

Homepage should be attractive without requiring the user to read a lot of text.

---

## Product Card

### Existing problem

Current product cards do not provide a premium commerce experience.

### Desired behavior

Provide enough information to compare products quickly.

### Components

```text
ProductCard
ProductImage
ProductBadge
WishlistButton
ProductPrice
VariantIndicator
AddToCartButton
```

### Acceptance

- consistent image ratio
- clear price
- clear stock
- clear CTA
- accessible wishlist
- keyboard accessible

---

## Catalogue

### Existing problem

The filters work but the presentation is too utilitarian.

### Desired behavior

Use a professional ecommerce catalogue experience.

### Components

```text
CatalogToolbar
FilterSidebar
MobileFilterDrawer
FilterChip
SortMenu
ProductGrid
Pagination
```

### Acceptance

- URL query parameters preserve filters
- pagination works
- filtering works
- sorting works
- mobile UX remains easy

---

## Product Page

### Existing problem

Useful information exists but hierarchy and purchase UX need improvement.

### Desired behavior

Make product suitability obvious.

### Components

```text
ProductGallery
ProductSummary
VariantSelector
QuantityControl
PurchaseActions
TrustStrip
ProductInfoTabs
RelatedProducts
ReviewSection
```

### Acceptance

The user can determine:

- what it is
- how much it costs
- whether it is available
- whether it suits their aquarium
- how to purchase it

without leaving the page.

---

## Checkout

### Existing problem

The backend flow is reasonably strong, but the experience can be simpler and clearer.

### Desired behavior

Three-stage checkout:

```text
Delivery
Payment
Review
```

### Acceptance

- validation errors are clear
- order summary remains visible
- mobile experience is easy
- server-side stock protection remains unchanged

---

# 40. Final Definition of Done

The redesign is complete only when all of the following are true.

## Visual

- coherent design system
- no accidental Bootstrap look
- strong hierarchy
- consistent spacing
- consistent cards
- clear CTA hierarchy
- good photography
- polished mobile layout

## UX

- navigation is clear
- search is useful
- filtering is easy
- product comparison is easy
- product suitability is clear
- cart is understandable
- checkout is straightforward

## Technical

- existing backend behavior preserved
- no unnecessary frontend rewrite
- reusable Blade components
- maintainable CSS/design tokens
- production configuration safe

## Accessibility

- keyboard navigation
- visible focus
- labels
- contrast
- screen-reader sensible structure
- appropriate touch sizes
- reduced motion

## SEO

- metadata
- canonical
- Open Graph
- schema
- sitemap
- robots
- product/category SEO

## Testing

- PHPUnit passes
- Feature tests pass
- Playwright E2E passes
- visual regression baseline passes
- accessibility checks pass

## Deployment

- production debug disabled
- no default credentials
- migrations controlled
- deployment logs clean
- application starts reliably

---

# 41. Final Priority List

Implement in this order:

```text
P0
1. Production security/configuration cleanup
2. Design system
3. Header/navigation
4. Homepage
5. Product cards
6. Catalogue/discovery
7. Playwright foundation

P1
8. Product detail
9. Mobile optimization
10. SEO
11. Accessibility
12. Performance
13. Visual regression

P2
14. Aquarium Finder
15. Reviews/social proof
16. Personalization
17. Advanced recommendations
18. Micro-interactions
```

---

# 42. Final Product Vision

The target is not:

> "Make the existing Bootstrap site prettier."

The target is:

> **Transform Sordar Agro into a credible, modern aquarium ecommerce brand while preserving the existing Laravel commerce foundation.**

The final site should feel:

**natural + modern + beautiful + fast + trustworthy + easy to use + technically robust + highly testable**

And it should be especially strong at one thing competitors cannot easily copy:

> **Helping aquarium hobbyists choose the right products for their actual aquarium.**
