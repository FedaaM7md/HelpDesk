<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\TicketCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $categories = TicketCategory::query()->active()->get();
        $articles = KbArticle::query()
            ->published()
            ->forLocale(app()->getLocale())
            ->latest('published_at')
            ->limit(6)
            ->get();

        $articleCount = KbArticle::query()->published()->forLocale(app()->getLocale())->count();

        return view('public.home', [
            'categories' => $categories,
            'articles' => $articles,
            'articleCount' => $articleCount,
        ]);
    }
}
