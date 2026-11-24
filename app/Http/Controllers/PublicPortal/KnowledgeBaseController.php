<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $term = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));

        $articles = KbArticle::query()
            ->published()
            ->forLocale($locale)
            ->with('category')
            ->when($term !== '', fn ($query) => $query->search($term))
            ->when($categorySlug !== '', fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $categories = KbCategory::query()
            ->active()
            ->withCount(['articles' => fn ($query) => $query->published()->forLocale($locale)])
            ->get();

        return view('public.kb.index', [
            'articles' => $articles,
            'categories' => $categories,
            'term' => $term,
            'categorySlug' => $categorySlug,
        ]);
    }

    public function category(KbCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $articles = $category->articles()
            ->published()
            ->forLocale(app()->getLocale())
            ->latest('published_at')
            ->paginate(9);

        return view('public.kb.category', [
            'category' => $category,
            'articles' => $articles,
            'categories' => KbCategory::query()->active()->get(),
        ]);
    }

    public function show(KbArticle $article): View
    {
        abort_unless($article->isPublished(), 404);
        abort_unless($article->locale === app()->getLocale(), 404);

        $article->increment('views');

        $related = KbArticle::query()
            ->published()
            ->forLocale(app()->getLocale())
            ->where('kb_category_id', $article->kb_category_id)
            ->whereKeyNot($article->id)
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('public.kb.show', [
            'article' => $article->load('category'),
            'related' => $related,
        ]);
    }
}
