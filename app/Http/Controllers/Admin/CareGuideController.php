<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCareGuideRequest;
use App\Models\CareGuide;
use Illuminate\Support\Str;

class CareGuideController extends Controller
{
    public function index()
    {
        $guides = CareGuide::latest()->paginate(12);

        return view('admin.care.index', compact('guides'));
    }

    public function create()
    {
        return view('admin.care.create');
    }

    public function store(SaveCareGuideRequest $request)
    {
        $data = $request->validated();

        CareGuide::create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'image' => $request->hasFile('image')
                                ? $request->file('image')->store('guides', 'public') : null,
            'author_id' => auth()->id(),
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        return redirect()->route('admin.care.index')->with('success', 'Care guide saved.');
    }

    public function edit(CareGuide $careGuide)
    {
        return view('admin.care.edit', ['guide' => $careGuide]);
    }

    public function update(SaveCareGuideRequest $request, CareGuide $careGuide)
    {
        $data = $request->validated();

        $careGuide->update([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'image' => $request->hasFile('image')
                                ? $request->file('image')->store('guides', 'public') : $careGuide->image,
            'published_at' => $request->boolean('publish') ? ($careGuide->published_at ?? now()) : null,
        ]);

        return redirect()->route('admin.care.index')->with('success', 'Care guide updated.');
    }

    public function destroy(CareGuide $careGuide)
    {
        $careGuide->delete();

        return back()->with('success', 'Care guide deleted.');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;
        while (CareGuide::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
