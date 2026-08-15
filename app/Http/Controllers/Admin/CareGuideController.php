<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareGuide;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $data = $this->validateGuide($request);

        CareGuide::create([
            'title'        => $data['title'],
            'slug'         => $this->uniqueSlug($data['title']),
            'excerpt'      => $data['excerpt'] ?? null,
            'content'      => $data['content'],
            'image'        => $request->hasFile('image')
                                ? $request->file('image')->store('guides', 'public') : null,
            'author_id'    => auth()->id(),
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        return redirect()->route('admin.care.index')->with('success', 'Care guide saved.');
    }

    public function edit(CareGuide $careGuide)
    {
        return view('admin.care.edit', ['guide' => $careGuide]);
    }

    public function update(Request $request, CareGuide $careGuide)
    {
        $data = $this->validateGuide($request);

        $careGuide->update([
            'title'        => $data['title'],
            'excerpt'      => $data['excerpt'] ?? null,
            'content'      => $data['content'],
            'image'        => $request->hasFile('image')
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

    private function validateGuide(Request $request): array
    {
        return $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['required', 'string'],
            'image'   => ['nullable', 'image', 'max:2048'],
            'publish' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base; $i = 1;
        while (CareGuide::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
