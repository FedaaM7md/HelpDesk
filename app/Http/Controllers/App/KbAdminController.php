<?php

namespace App\Http\Controllers\App;

use App\Enums\KbStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKbArticleRequest;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KbAdminController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $articles = KbArticle::query()
            ->with(['category', 'author'])
            ->when($term !== '', fn ($query) => $query->search($term))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('locale'), fn ($query, $locale) => $query->where('locale', $locale))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('app.kb.index', [
            'articles' => $articles,
            'term' => $term,
            'categories' => KbCategory::query()->orderBy('name_en')->get(),
            'statuses' => KbStatus::options(),
        ]);
    }

    public function create(): View
    {
        return view('app.kb.form', [
            'article' => new KbArticle(['locale' => app()->getLocale(), 'status' => KbStatus::Draft]),
            'categories' => KbCategory::query()->orderBy('name_en')->get(),
            'translationKeys' => $this->translationKeys(),
            'statuses' => KbStatus::options(),
        ]);
    }

    public function store(StoreKbArticleRequest $request): RedirectResponse
    {
        $article = KbArticle::create($this->payload($request));

        return redirect()
            ->route('app.kb.edit', $article)
            ->with('status', __('app.kb.created'));
    }

    public function edit(KbArticle $article): View
    {
        return view('app.kb.form', [
            'article' => $article,
            'categories' => KbCategory::query()->orderBy('name_en')->get(),
            'translationKeys' => $this->translationKeys($article->translation_key),
            'statuses' => KbStatus::options(),
        ]);
    }

    public function update(StoreKbArticleRequest $request, KbArticle $article): RedirectResponse
    {
        $article->update($this->payload($request, $article));

        return redirect()->route('app.kb.edit', $article)->with('status', __('app.kb.updated'));
    }

    public function destroy(KbArticle $article): RedirectResponse
    {
        Audit::record('kb.deleted', $article, ['title' => $article->title]);
        $article->delete();

        return redirect()->route('app.kb.index')->with('status', __('app.kb.deleted'));
    }

    public function togglePublish(KbArticle $article): RedirectResponse
    {
        if ($article->isPublished()) {
            $article->update(['status' => KbStatus::Draft, 'published_at' => null]);
            Audit::record('kb.unpublished', $article, ['title' => $article->title]);
        } else {
            $article->update(['status' => KbStatus::Published, 'published_at' => now()]);
            Audit::record('kb.published', $article, ['title' => $article->title]);
        }

        return back()->with('status', __('app.kb.visibility_changed'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(StoreKbArticleRequest $request, ?KbArticle $article = null): array
    {
        $data = $request->validated();
        $status = KbStatus::from($data['status']);

        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '') {
            $base = Str::slug($data['title']) ?: 'article-'.Str::lower(Str::random(8));
            $slug = $base;
            $counter = 2;

            while (KbArticle::query()->where('slug', $slug)->whereKeyNot($article?->id)->exists()) {
                $slug = $base.'-'.$counter++;
            }
        }

        return [
            'kb_category_id' => $data['kb_category_id'],
            'author_id' => $article?->author_id ?? $request->user()->id,
            'locale' => $data['locale'],
            'translation_key' => $data['translation_key'] ?: ($article?->translation_key ?? (string) Str::uuid()),
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'status' => $status,
            'published_at' => $status === KbStatus::Published
                ? ($article?->published_at ?? now())
                : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function translationKeys(?string $current = null): array
    {
        $keys = KbArticle::query()
            ->select('translation_key', 'title', 'locale')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn (KbArticle $article) => [
                $article->translation_key => $article->translation_key.' — '.$article->title.' ('.$article->locale.')',
            ])
            ->all();

        if ($current && ! array_key_exists($current, $keys)) {
            $keys[$current] = $current;
        }

        return $keys;
    }
}
