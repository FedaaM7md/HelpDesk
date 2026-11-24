<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('app.taxonomy.tags', [
            'tags' => Tag::query()->withCount('tickets')->orderBy('name_en')->get(),
            'colors' => ['slate', 'teal', 'indigo', 'amber', 'rose'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:60'],
            'name_en' => ['required', 'string', 'max:60'],
            'color' => ['required', Rule::in(['slate', 'teal', 'indigo', 'amber', 'rose'])],
        ]);

        $base = Str::slug($validated['name_en']) ?: 'tag-'.Str::lower(Str::random(6));
        $slug = $base;
        $counter = 2;

        while (Tag::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        Tag::create([...$validated, 'slug' => $slug]);

        return back()->with('status', __('app.tags.created'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:60'],
            'name_en' => ['required', 'string', 'max:60'],
            'color' => ['required', Rule::in(['slate', 'teal', 'indigo', 'amber', 'rose'])],
        ]);

        $tag->update($validated);

        return back()->with('status', __('app.tags.updated'));
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return back()->with('status', __('app.tags.deleted'));
    }
}
