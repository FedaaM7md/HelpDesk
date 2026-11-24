<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports) {}

    public function index(Request $request): View
    {
        [$from, $to, $filters] = $this->filters($request);

        return view('app.reports.index', [
            'report' => $this->reports->build($from, $to, $filters, $request->user()),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'filters' => $filters,
            'agents' => User::query()->active()->whereIn('role', ['agent', 'supervisor'])->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $filters] = $this->filters($request);
        $report = $this->reports->build($from, $to, $filters, $request->user());

        return response()->streamDownload(
            fn () => print $this->reports->toCsv($report),
            'helpdesk-report-'.$from->toDateString().'-'.$to->toDateString().'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @return array{0:Carbon, 1:Carbon, 2:array<string, mixed>}
     */
    protected function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'agent' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
        ]);

        $from = isset($validated['from']) ? Carbon::createFromFormat('Y-m-d', $validated['from']) : now()->subDays(29);
        $to = isset($validated['to']) ? Carbon::createFromFormat('Y-m-d', $validated['to']) : now();

        if ($from->diffInDays($to, true) > 366) {
            abort(422, __('reports.range_too_long'));
        }

        return [$from, $to, array_filter([
            'agent' => $validated['agent'] ?? null,
            'priority' => $validated['priority'] ?? null,
        ])];
    }
}
