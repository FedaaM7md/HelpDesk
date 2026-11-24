<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('public.pages.about');
    }

    public function privacy(): View
    {
        return view('public.pages.privacy');
    }

    public function terms(): View
    {
        return view('public.pages.terms');
    }
}
