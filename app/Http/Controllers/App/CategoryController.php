<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('app.taxonomy.categories', [
            'categories' => TicketCategory::query()->withCount('tickets')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $base = Str::slug($validated['name_en']) ?: 'category-'.Str::lower(Str::random(6));
        $slug = $base;
        $counter = 2;

        while (TicketCategory::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        TicketCategory::create([
            ...$validated,
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', __('app.categories.created'));
    }

    public function update(Request $request, TicketCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['required', 'string', 'max:100'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('app.categories.updated'));
    }

    public function destroy(TicketCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('status', __('app.categories.deleted'));
    }
}
