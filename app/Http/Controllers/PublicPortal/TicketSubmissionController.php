<?php

namespace App\Http\Controllers\PublicPortal;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketSubmissionController extends Controller
{
    public function __construct(protected TicketService $tickets) {}

    public function create(Request $request): View
    {
        $selectedCategory = $request->query('category')
            ? TicketCategory::query()->where('slug', $request->query('category'))->where('is_active', true)->first()
            : null;

        $maxKilobytes = (int) config('helpdesk.attachments.max_kilobytes', 5120);

        return view('public.submit', [
            'categories' => TicketCategory::query()->active()->get(),
            'selectedCategory' => $selectedCategory,
            'maxFiles' => (int) config('helpdesk.attachments.max_files', 5),
            'maxSize' => $maxKilobytes >= 1024
                ? round($maxKilobytes / 1024, 1).' MB'
                : $maxKilobytes.' KB',
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->create([
            'subject' => $request->string('subject')->trim()->toString(),
            'description' => $request->string('message')->trim()->toString(),
            'requester_name' => $request->string('requester_name')->trim()->toString(),
            'requester_email' => $request->string('requester_email')->toString(),
            'category_id' => $request->integer('category_id'),
            'requester_locale' => app()->getLocale(),
            'channel' => TicketChannel::Portal,
            'priority' => TicketPriority::Normal,
        ], $request->file('attachments', []));

        return redirect()
            ->to($this->tickets->trackingUrl($ticket))
            ->with('status', __('portal.submit.created', ['reference' => $ticket->reference]));
    }
}
