<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\RejectSubmissionRequest;
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

    public function reject(RejectSubmissionRequest $request, CommunitySubmission $submission)
    {
        $data = $request->validated();

        $submission->update([
            'status' => 'rejected',
            'admin_feedback' => $data['admin_feedback'] ?? null,
        ]);

        return back()->with('success', 'Submission rejected.');
    }
}
