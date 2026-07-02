<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySubmission;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = CommunitySubmission::with('user')->latest();
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $submissions = $query->paginate(12)->withQueryString();

        return view('admin.community.index', compact('submissions', 'status'));
    }

    public function approve(CommunitySubmission $submission)
    {
        $submission->update(['status' => 'approved', 'admin_feedback' => null]);
        return back()->with('success', 'Submission approved and published.');
    }

    public function reject(Request $request, CommunitySubmission $submission)
    {
        $data = $request->validate(['admin_feedback' => ['nullable', 'string', 'max:1000']]);
        $submission->update(['status' => 'rejected', 'admin_feedback' => $data['admin_feedback'] ?? null]);
        return back()->with('success', 'Submission rejected.');
    }
}
