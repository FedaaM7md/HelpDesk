<?php

use Carbon\Carbon;

if (! function_exists('hd_datetime')) {
    function hd_datetime($value): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)
            ->setTimezone(config('helpdesk.timezone', 'UTC'))
            ->locale(app()->getLocale())
            ->translatedFormat(app()->getLocale() === 'ar' ? 'j F Y، g:i A' : 'j F Y, g:i A');
    }
}

if (! function_exists('hd_date')) {
    function hd_date($value): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)
            ->setTimezone(config('helpdesk.timezone', 'UTC'))
            ->locale(app()->getLocale())
            ->translatedFormat('j F Y');
    }
}

if (! function_exists('hd_duration')) {
    function hd_duration(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $minutes = max(0, $minutes);

        if ($minutes < 60) {
            return __('common.duration_minutes', ['count' => $minutes]);
        }

        if ($minutes < 1440) {
            $hours = intdiv($minutes, 60);
            $rest = $minutes % 60;

            return $rest > 0
                ? __('common.duration_hours_minutes', ['hours' => $hours, 'minutes' => $rest])
                : __('common.duration_hours', ['count' => $hours]);
        }

        $days = intdiv($minutes, 1440);
        $rest = intdiv($minutes % 1440, 60);

        return $rest > 0
            ? __('common.duration_days_hours', ['days' => $days, 'hours' => $rest])
            : __('common.duration_days', ['count' => $days]);
    }
}

if (! function_exists('hd_relative')) {
    function hd_relative($value): string
    {
        if (! $value) {
            return '—';
        }

        $date = Carbon::parse($value);

        return $date->locale(app()->getLocale())->diffForHumans(short: true);
    }
}
