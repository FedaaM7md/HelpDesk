<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCannedReplyRequest;
use App\Models\CannedReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CannedReplyController extends Controller
{
    public function index(Request $request): View
    {
        $query = CannedReply::query()->orderBy('title_en');

        if (! $request->user()->can('manage-canned-replies')) {
            $query->active();
        }

        return view('app.canned-replies.index', [
            'replies' => $query->get(),
            'canManage' => $request->user()->can('manage-canned-replies'),
        ]);
    }

    public function store(StoreCannedReplyRequest $request): RedirectResponse
    {
        CannedReply::create([
            'title_ar' => $request->string('title_ar')->trim()->toString(),
            'title_en' => $request->string('title_en')->trim()->toString(),
            'body_ar' => $request->string('body_ar')->trim()->toString(),
            'body_en' => $request->string('body_en')->trim()->toString(),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', __('app.canned.created'));
    }

    public function update(StoreCannedReplyRequest $request, CannedReply $cannedReply): RedirectResponse
    {
        $cannedReply->update([
            'title_ar' => $request->string('title_ar')->trim()->toString(),
            'title_en' => $request->string('title_en')->trim()->toString(),
            'body_ar' => $request->string('body_ar')->trim()->toString(),
            'body_en' => $request->string('body_en')->trim()->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('app.canned.updated'));
    }

    public function destroy(CannedReply $cannedReply): RedirectResponse
    {
        $this->authorize('manage-canned-replies');

        $cannedReply->delete();

        return back()->with('status', __('app.canned.deleted'));
    }
}
