<?php

namespace App\Http\Controllers;

use App\Models\CareGuide;

class CareGuideController extends Controller
{
    public function index()
    {
        $guides = CareGuide::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(9);

        return view('care.index', compact('guides'));
    }

    public function show(CareGuide $careGuide)
    {
        abort_unless($careGuide->isPublished(), 404);

        return view('care.show', ['guide' => $careGuide]);
    }
}
