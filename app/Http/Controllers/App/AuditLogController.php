<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('app.audit.index', [
            'logs' => AuditLog::query()->with('user')->latest('id')->paginate(50),
        ]);
    }
}
