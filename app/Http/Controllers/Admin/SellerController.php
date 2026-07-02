<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Admin-only provisioning of Partner Sellers. The admin sets an email and a
 * temporary password; the seller is forced to change it on first login.
 */
class SellerController extends Controller
{
    public function index()
    {
        $sellers = User::where('role', 'seller')
            ->withCount('products')
            ->latest()
            ->paginate(15);

        return view('admin.sellers.index', compact('sellers'));
    }

    public function create()
    {
        // Suggest a random temporary password the admin can hand over.
        $suggested = Str::password(12, symbols: false);

        return view('admin.sellers.create', compact('suggested'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', Password::min(8)],
        ]);

        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'],
            'role'     => 'seller',
            'must_change_password' => true,        // forced change on first login
            'created_by' => auth()->id(),          // links seller to this admin
        ]);

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Partner seller created. Share the temporary password securely.');
    }
}
