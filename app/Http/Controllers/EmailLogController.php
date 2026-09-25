<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin > Emails > Historique. */
class EmailLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = EmailLog::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('recipient', 'like', '%'.$request->input('q').'%')
                ->orWhere('subject', 'like', '%'.$request->input('q').'%')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.emails.logs', [
            'logs' => $logs,
            'templateNames' => EmailTemplate::query()->pluck('name', 'key')->put('test', 'Email de test'),
        ]);
    }
}
