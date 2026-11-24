<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BusinessHoursController extends Controller
{
    public function edit(): View
    {
        $existing = BusinessHour::query()->orderBy('weekday')->get()->keyBy('weekday');

        $hours = collect(range(0, 6))->map(fn (int $weekday) => $existing->get($weekday) ?? new BusinessHour([
            'weekday' => $weekday,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_enabled' => false,
        ]));

        return view('app.settings.business-hours', [
            'hours' => $hours,
            'timezone' => config('helpdesk.timezone'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'array'],
            'days.*.opens_at' => ['nullable', 'date_format:H:i'],
            'days.*.closes_at' => ['nullable', 'date_format:H:i'],
        ]);

        $days = $request->input('days', []);
        $enabled = $request->input('enabled', []);

        foreach (range(0, 6) as $weekday) {
            $isEnabled = ! empty($enabled[$weekday]);
            $opens = $days[$weekday]['opens_at'] ?? null;
            $closes = $days[$weekday]['closes_at'] ?? null;

            if ($isEnabled) {
                if (! $opens || ! $closes) {
                    throw ValidationException::withMessages([
                        'days' => __('app.settings.hours_required'),
                    ]);
                }

                if ($closes <= $opens) {
                    throw ValidationException::withMessages([
                        'days' => __('app.settings.hours_order'),
                    ]);
                }
            }

            BusinessHour::updateOrCreate(
                ['weekday' => $weekday],
                [
                    'opens_at' => $opens ?: '09:00',
                    'closes_at' => $closes ?: '17:00',
                    'is_enabled' => $isEnabled,
                ],
            );
        }

        Audit::record('business_hours.updated');

        return back()->with('status', __('app.settings.hours_saved'));
    }
}
