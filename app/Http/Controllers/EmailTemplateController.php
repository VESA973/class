<?php

namespace App\Http\Controllers;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin > Emails > Modeles. */
class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $counts = EmailLog::query()->where('status', 'sent')->selectRaw('template_key, count(*) as total')->groupBy('template_key')->pluck('total', 'template_key');

        return view('admin.emails.templates', [
            'templates' => EmailTemplate::query()->orderBy('id')->get(),
            'counts' => $counts,
        ]);
    }

    public function edit(EmailTemplate $template): View
    {
        return view('admin.emails.edit-template', ['template' => $template]);
    }

    public function update(Request $request, EmailTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ], [
            'subject.required' => "Indiquez l'objet de l'email.",
            'body.required' => "Le contenu de l'email ne peut pas être vide.",
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $template->update($data);

        return redirect()->route('admin.emails.templates.edit', $template)->with('status', "Modèle « {$template->name} » enregistré.");
    }

    /** Apercu en direct pendant la saisie (valeurs d'exemple). */
    public function preview(Request $request, EmailTemplate $template, EmailService $emails): JsonResponse
    {
        $data = $request->validate(['subject' => ['nullable', 'string', 'max:255'], 'body' => ['nullable', 'string', 'max:10000']]);
        $rendered = EmailTemplate::renderContent($data['subject'] ?? '', $data['body'] ?? '', EmailTemplate::sampleVariables() + $emails->siteVariables());

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => TemplatedMail::renderHtml($rendered['subject'], $rendered['html']),
        ]);
    }
}
