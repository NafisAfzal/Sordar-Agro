@extends('layouts.dashboard')
@section('title', 'Care Guides')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Care guides</h3>
        <a href="{{ route('admin.care.create') }}" class="btn btn-sa"><i class="bi bi-plus-lg"></i> New guide</a>
    </div>

    @if ($guides->isEmpty())
        <div class="alert alert-info">No care guides yet.</div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Title</th><th>Published</th><th>Author</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($guides as $g)
                            <tr>
                                <td class="fw-semibold">{{ $g->title }}</td>
                                <td>
                                    @if ($g->isPublished())<span class="badge bg-success">Live</span>
                                    @elseif ($g->published_at)<span class="badge bg-info">Scheduled</span>
                                    @else<span class="badge bg-secondary">Draft</span>@endif
                                </td>
                                <td class="small">{{ $g->author->name ?? '—' }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.care.edit', $g) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('admin.care.destroy', $g) }}" class="d-inline" onsubmit="return confirm('Delete this guide?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $guides->links() }}</div>
    @endif
@endsection
