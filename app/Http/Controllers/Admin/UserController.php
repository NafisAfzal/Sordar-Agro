<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($term = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%"));
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /** Suspend or re-activate any account (admins excluded for safety). */
    public function toggle(User $user)
    {
        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be suspended from the app.');

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success',
            $user->is_active ? 'Account activated.' : 'Account suspended.');
    }
}
