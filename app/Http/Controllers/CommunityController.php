<?php

namespace App\Http\Controllers;

use App\Models\CommunitySubmission;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    /** Public "Community Knowledge" — approved submissions only. */
    public function index()
    {
        $submissions = CommunitySubmission::with('user')
            ->where('status', 'approved')
            ->latest()
            ->paginate(10);

        return view('community.index', compact('submissions'));
    }

    public function create()
    {
        return view('community.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body'  => ['required', 'string', 'max:5000'],
        ]);

        CommunitySubmission::create([
            'user_id' => auth()->id(),
            'title'   => $data['title'],
            'body'    => $data['body'],
            'status'  => 'pending',
        ]);

        return redirect()->route('community.index')
            ->with('success', 'Thanks! Your contribution is pending admin review.');
    }
}
