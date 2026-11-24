<?php

namespace App\Http\Controllers\App;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\SlaPolicy;
use App\Services\SlaService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlaPolicyController extends Controller
{
    public function index(): View
    {
        $policies = SlaPolicy::query()->get()->keyBy(fn (SlaPolicy $policy) => $policy->priority->value);

        return view('app.sla.index', [
            'policies' => $policies,
            'priorities' => TicketPriority::cases(),
            'fallback' => SlaService::FALLBACK,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policies' => ['required', 'array'],
            'policies.*.first_response_minutes' => ['required', 'integer', 'min:5', 'max:525600'],
            'policies.*.resolution_minutes' => ['required', 'integer', 'min:5', 'max:525600'],
        ]);

        foreach (TicketPriority::cases() as $priority) {
            $row = $validated['policies'][$priority->value] ?? null;

            if (! $row) {
                continue;
            }

            SlaPolicy::updateOrCreate(
                ['priority' => $priority->value],
                [
                    'first_response_minutes' => (int) $row['first_response_minutes'],
                    'resolution_minutes' => (int) $row['resolution_minutes'],
                    'use_business_hours' => (bool) ($row['use_business_hours'] ?? false),
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ],
            );
        }

        Audit::record('sla.updated');

        return back()->with('status', __('app.sla.updated'));
    }
}
