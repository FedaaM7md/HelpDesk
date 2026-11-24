<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\CsatRating;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $base = Ticket::query()->visibleTo($user);

        $metrics = [
            'open' => (clone $base)->unresolved()->count(),
            'unassigned' => (clone $base)->unresolved()->whereNull('assigned_to')->count(),
            'urgent' => (clone $base)->unresolved()->where('priority', 'urgent')->count(),
            'breached' => (clone $base)->unresolved()->filter(['sla' => 'breached'])->count(),
            'mine' => (clone $base)->unresolved()->where('assigned_to', $user->id)->count(),
            'solved_today' => (clone $base)->whereDate('resolved_at', today())->count(),
        ];

        $csatAverage = CsatRating::query()
            ->where('submitted_at', '>=', now()->subDays(30))
            ->whereHas('ticket', fn ($query) => $query->visibleTo($user))
            ->avg('score');

        $recent = (clone $base)
            ->with(['category', 'assignee'])
            ->latest()
            ->limit(10)
            ->get();

        $attention = (clone $base)
            ->unresolved()
            ->with(['category', 'assignee'])
            ->orderByRaw('first_response_due_at is null, first_response_due_at asc')
            ->limit(8)
            ->get();

        $statusCounts = (clone $base)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('app.dashboard', [
            'metrics' => $metrics,
            'csatAverage' => $csatAverage !== null ? round((float) $csatAverage, 2) : null,
            'recent' => $recent,
            'attention' => $attention,
            'statusCounts' => $statusCounts,
            'categories' => TicketCategory::query()->active()->get(),
        ]);
    }
}
