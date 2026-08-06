@extends('layouts.app')
@section('title', 'Contribute to Community')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h3 class="mb-4">Share with the community</h3>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('community.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your tip / experience</label>
                        <textarea name="body" rows="6" class="form-control" required>{{ old('body') }}</textarea>
                    </div>
                    <p class="small text-muted">Your post will be reviewed by an admin before appearing publicly.</p>
                    <button class="btn btn-sa">Submit for review</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
