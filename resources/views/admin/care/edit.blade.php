@extends('layouts.dashboard')
@section('title', 'Edit Care Guide')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Edit care guide</h3>
    <div class="card border-0 shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('admin.care.update', $guide) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            @include('admin.care._form')
            <button class="btn btn-sa">Update guide</button>
            <a href="{{ route('admin.care.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div></div>
@endsection
