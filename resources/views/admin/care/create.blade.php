@extends('layouts.dashboard')
@section('title', 'New Care Guide')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">New care guide</h3>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('admin.care.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.care._form', ['guide' => null])
            <button class="btn btn-sa">Save guide</button>
            <a href="{{ route('admin.care.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div></div>
@endsection
