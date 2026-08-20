{{-- Shared create/edit form. Expects $product (nullable) and $categories. --}}
@php
    $product = $product ?? null;
    $isFish  = old('is_fish', $product->is_fish ?? false);
    // Build a lookup of existing variants by size for edit mode.
    $vBySize = [];
    if ($product) {
        foreach ($product->variants as $v) { $vBySize[$v->size] = $v; }
    }
    $val = fn($size, $field, $default = '') =>
        old("variants_$size.$field", isset($vBySize[$size]) ? $vBySize[$size]->$field : $default);
@endphp

<div class="row">
    <div class="col-md-8">
        <div class="mb-3">
            <label class="form-label">Product name</label>
            <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
                <option value="">Choose…</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description ?? '') }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Marketplace Share per Sale (TK)</label>
            <input type="number" step="0.01" min="0.01" name="profit_share_amount"
                   value="{{ old('profit_share_amount', $product->profit_share_amount ?? '') }}"
                   class="form-control" required>
            <small class="text-muted">For every unit sold, you'll share this amount (TK) with the marketplace.</small>
        </div>
    </div>

    <div class="col-md-4">
        <div class="mb-3">
    <label class="form-label">Thumbnail image</label>
    <input type="file" name="thumbnail" id="thumbnailInput" class="form-control" accept="image/*">
    <small class="text-muted">JPG, PNG, or WEBP. Max 2MB.</small>
    <div class="mt-2">
        <img id="thumbnailPreview"
             src="{{ $product && $product->thumbnail ? asset('storage/'.$product->thumbnail) : '' }}"
             class="img-fluid rounded {{ $product && $product->thumbnail ? '' : 'd-none' }}"
             style="max-height:180px;" alt="Thumbnail preview">
    </div>
</div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_fish" value="1" id="isFish" @checked($isFish)>
            <label class="form-check-label" for="isFish">This is a fish (sold in pairs, 3 sizes)</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Min. tank size (L)</label>
            <input type="number" name="min_tank_size_litres" value="{{ old('min_tank_size_litres', $product->min_tank_size_litres ?? '') }}" class="form-control" min="0">
        </div>
        <div class="mb-3">
            <label class="form-label">Temperament</label>
            <select name="temperament" class="form-select">
                <option value="">N/A</option>
                @foreach (['peaceful','semi-aggressive','aggressive'] as $t)
                    <option value="{{ $t }}" @selected(old('temperament', $product->temperament ?? '') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<hr>

{{-- Fish variants: small / medium / large --}}
<div id="fishVariants" class="{{ $isFish ? '' : 'd-none' }}">
    <h6 class="fw-bold">Pricing &amp; stock per size <small class="text-muted">(stock = number of pairs)</small></h6>
    @foreach (['small','medium','large'] as $i => $size)
        <div class="row g-2 align-items-end mb-2 fish-row">
            <div class="col-md-2"><label class="form-label small text-capitalize">{{ $size }}</label>
                <input type="hidden" name="variants[{{ $i }}][size]" value="{{ $size }}" class="fish-input">
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" min="0" name="variants[{{ $i }}][price]" value="{{ $val($size, 'price') }}" class="form-control fish-input" placeholder="Price ৳">
            </div>
            <div class="col-md-2">
                <input type="number" min="0" name="variants[{{ $i }}][stock]" value="{{ $val($size, 'stock') }}" class="form-control fish-input" placeholder="Pairs">
            </div>
            <div class="col-md-5">
                <input type="text" name="variants[{{ $i }}][size_description]" value="{{ $val($size, 'size_description') }}" class="form-control fish-input" placeholder="e.g. 2–3 cm juveniles">
            </div>
        </div>
    @endforeach
</div>

{{-- Non-fish: single standard variant --}}
<div id="standardVariant" class="{{ $isFish ? 'd-none' : '' }}">
    <h6 class="fw-bold">Pricing &amp; stock</h6>
    <div class="row g-2 align-items-end">
        <input type="hidden" name="variants[0][size]" value="standard" class="std-input">
        <div class="col-md-3">
            <label class="form-label small">Price ৳</label>
            <input type="number" step="0.01" min="0" name="variants[0][price]" value="{{ $val('standard','price') }}" class="form-control std-input">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Stock (units)</label>
            <input type="number" min="0" name="variants[0][stock]" value="{{ $val('standard','stock') }}" class="form-control std-input">
        </div>
        <div class="col-md-6">
            <label class="form-label small">Note (optional)</label>
            <input type="text" name="variants[0][size_description]" value="{{ $val('standard','size_description') }}" class="form-control std-input">
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Live thumbnail preview before upload.
    (function () {
        const input = document.getElementById('thumbnailInput');
        const preview = document.getElementById('thumbnailPreview');
        if (!input || !preview) return;

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                alert('Image is too large. Please choose a file under 2MB.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    })();
</script>
<script>
    // Toggle which variant block submits. Disabled inputs are not posted,
    // so only the active block's fields reach the controller.
    (function () {
        const cb   = document.getElementById('isFish');
        const fish = document.getElementById('fishVariants');
        const std  = document.getElementById('standardVariant');

        function sync() {
            const isFish = cb.checked;
            fish.classList.toggle('d-none', !isFish);
            std.classList.toggle('d-none', isFish);
            document.querySelectorAll('.fish-input').forEach(el => el.disabled = !isFish);
            document.querySelectorAll('.std-input').forEach(el => el.disabled = isFish);
        }
        cb.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush
