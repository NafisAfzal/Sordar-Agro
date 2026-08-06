@php $guide = $guide ?? null; @endphp
<div class="mb-3">
    <label class="form-label">Title</label>
    <input type="text" name="title" value="{{ old('title', $guide->title ?? '') }}" class="form-control" required>
</div>
<div class="mb-3">
    <label class="form-label">Excerpt <small class="text-muted">(short summary)</small></label>
    <input type="text" name="excerpt" value="{{ old('excerpt', $guide->excerpt ?? '') }}" class="form-control" maxlength="300">
</div>
<div class="mb-3">
    <label class="form-label">Content</label>
    <textarea name="content" rows="10" class="form-control" required>{{ old('content', $guide->content ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label class="form-label">Cover image</label>
    <input type="file" name="image" class="form-control" accept="image/*">
    @if ($guide && $guide->image)
        <img src="{{ asset('storage/'.$guide->image) }}" class="img-fluid rounded mt-2" style="max-height:160px;" alt="">
    @endif
</div>
<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" name="publish" value="1" id="publish" @checked(old('publish', $guide && $guide->published_at))>
    <label class="form-check-label" for="publish">Publish now</label>
</div>
