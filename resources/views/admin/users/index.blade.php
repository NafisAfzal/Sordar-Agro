@extends('layouts.dashboard')
@section('title', 'Users')
@section('sidebar') @include('partials.admin-sidebar') @endsection
@section('content')
    <h3 class="mb-4">Users</h3>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email"></div>
        <div class="col-md-3">
            <select name="role" class="form-select" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach (['customer','seller','admin'] as $r)
                    <option value="{{ $r }}" @selected(request('role') === $r)>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-sa w-100">Filter</button></div>
    </form>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td class="fw-semibold">{{ $u->name }}</td>
                            <td class="small">{{ $u->email }}</td>
                            <td><span class="badge bg-{{ ['customer'=>'primary','seller'=>'info','admin'=>'dark'][$u->role] }}">{{ ucfirst($u->role) }}</span></td>
                            <td>
                                @if ($u->is_active)<span class="badge bg-success">Active</span>
                                @else<span class="badge bg-danger">Suspended</span>@endif
                            </td>
                            <td>
                                @unless ($u->isAdmin())
                                    <form method="POST" action="{{ route('admin.users.toggle', $u) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-{{ $u->is_active ? 'danger' : 'success' }}">
                                            {{ $u->is_active ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">—</span>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links() }}</div>
@endsection
