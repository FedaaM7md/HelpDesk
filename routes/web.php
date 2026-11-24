<?php

// HelpDesk — © 2026 Fedaa Mohamed. All rights reserved. Proprietary; not open source.

use App\Http\Controllers\App\AuditLogController;
use App\Http\Controllers\App\BusinessHoursController;
use App\Http\Controllers\App\CannedReplyController;
use App\Http\Controllers\App\CategoryController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\KbAdminController;
use App\Http\Controllers\App\ReportController;
use App\Http\Controllers\App\SlaPolicyController;
use App\Http\Controllers\App\TagController;
use App\Http\Controllers\App\TicketController;
use App\Http\Controllers\App\UserController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicPortal\CsatController;
use App\Http\Controllers\PublicPortal\HomeController;
use App\Http\Controllers\PublicPortal\KnowledgeBaseController;
use App\Http\Controllers\PublicPortal\PageController;
use App\Http\Controllers\PublicPortal\TicketSubmissionController;
use App\Http\Controllers\PublicPortal\TicketTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('language.switch');

Route::get('/kb', [KnowledgeBaseController::class, 'index'])->name('kb.index');
Route::get('/kb/category/{category}', [KnowledgeBaseController::class, 'category'])->name('kb.category');
Route::get('/kb/{article}', [KnowledgeBaseController::class, 'show'])->name('kb.show');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
    Route::post('/demo/login', DemoLoginController::class)->name('demo.login');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/submit', [TicketSubmissionController::class, 'create'])->name('portal.submit');
Route::post('/submit', [TicketSubmissionController::class, 'store'])
    ->middleware('throttle:public-forms')
    ->name('portal.submit.store');

Route::get('/track', [TicketTrackingController::class, 'form'])->name('portal.track');
Route::post('/track', [TicketTrackingController::class, 'lookup'])
    ->middleware('throttle:tracking')
    ->name('portal.track.lookup');

Route::get('/t/{ticket}', [TicketTrackingController::class, 'show'])
    ->middleware('signed')
    ->name('portal.ticket.show');
Route::post('/t/{ticket}', [TicketTrackingController::class, 'reply'])
    ->middleware(['signed', 'throttle:public-forms'])
    ->name('portal.ticket.reply');

Route::get('/csat/{ticket}', [CsatController::class, 'show'])
    ->middleware('signed')
    ->name('portal.csat.show');
Route::post('/csat/{ticket}', [CsatController::class, 'store'])
    ->middleware(['signed', 'throttle:public-forms'])
    ->name('portal.csat.store');

Route::prefix('app')->name('app.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('app.dashboard'))->name('dashboard.redirect');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/note', [TicketController::class, 'note'])->name('tickets.note');
    Route::post('/tickets/{ticket}/solve', [TicketController::class, 'solve'])->name('tickets.solve');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
    Route::post('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    Route::get('/canned-replies', [CannedReplyController::class, 'index'])->name('canned.index');
    Route::post('/canned-replies', [CannedReplyController::class, 'store'])->name('canned.store');
    Route::patch('/canned-replies/{cannedReply}', [CannedReplyController::class, 'update'])->name('canned.update');
    Route::delete('/canned-replies/{cannedReply}', [CannedReplyController::class, 'destroy'])->name('canned.destroy');

    Route::middleware('can:manage-taxonomy')->group(function () {
        Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
        Route::patch('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/sla', [SlaPolicyController::class, 'index'])->name('sla.index');
        Route::put('/sla', [SlaPolicyController::class, 'update'])->name('sla.update');

        Route::get('/settings/business-hours', [BusinessHoursController::class, 'edit'])->name('settings.business-hours');
        Route::put('/settings/business-hours', [BusinessHoursController::class, 'update'])->name('settings.business-hours.update');
    });

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('can:view-audit')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware('can:manage-kb')->group(function () {
        Route::get('/kb', [KbAdminController::class, 'index'])->name('kb.index');
        Route::get('/kb/create', [KbAdminController::class, 'create'])->name('kb.create');
        Route::post('/kb', [KbAdminController::class, 'store'])->name('kb.store');
        Route::get('/kb/{article}/edit', [KbAdminController::class, 'edit'])->name('kb.edit');
        Route::patch('/kb/{article}', [KbAdminController::class, 'update'])->name('kb.update');
        Route::delete('/kb/{article}', [KbAdminController::class, 'destroy'])->name('kb.destroy');
        Route::post('/kb/{article}/publish', [KbAdminController::class, 'togglePublish'])->name('kb.publish');
    });

    Route::middleware('can:view-reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    });
});
