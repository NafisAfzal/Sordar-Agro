<?php

namespace App\Http\Controllers;

use App\Http\Requests\Community\StoreSubmissionRequest;
use App\Models\CommunitySubmission;

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

    public function store(StoreSubmissionRequest $request)
    {
        $data = $request->validated();

        CommunitySubmission::create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'body' => $data['body'],
            'status' => 'pending',
        ]);

        return redirect()->route('community.index')
            ->with('success', 'Thanks! Your contribution is pending admin review.');
    }
}
