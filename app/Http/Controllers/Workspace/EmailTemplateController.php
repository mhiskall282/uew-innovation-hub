<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Services\AuditLogger;
use App\Support\EmailTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Editable system e-mails. Routes are gated by can:settings.manage. */
class EmailTemplateController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $templates = collect(EmailTemplates::definitions())
            ->map(fn ($definition, $key) => $definition + ['key' => $key, 'customised' => EmailTemplates::content($key)['customised']])
            ->groupBy('group');

        return view('workspace.templates.index', ['groups' => $templates]);
    }

    public function edit(string $template): View
    {
        abort_unless(EmailTemplates::exists($template), 404);

        return view('workspace.templates.edit', [
            'key' => $template,
            'definition' => EmailTemplates::definitions()[$template],
            'content' => EmailTemplates::content($template),
        ]);
    }

    public function update(Request $request, string $template): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($template), 404);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $record = EmailTemplate::firstOrNew(['key' => $template]);
        $record->fill($data);
        $record->updated_by = $request->user()->id;
        $record->save();
        EmailTemplates::flush();

        $this->audit->record('template.updated', "Updated e-mail template \"{$template}\"");

        return back()->with('status', 'Template saved. New e-mails use it straight away.');
    }

    public function reset(string $template): RedirectResponse
    {
        abort_unless(EmailTemplates::exists($template), 404);

        EmailTemplate::where('key', $template)->delete();
        EmailTemplates::flush();
        $this->audit->record('template.reset', "Reset e-mail template \"{$template}\" to the default");

        return back()->with('status', 'Template restored to the default wording.');
    }

    /** Rendered HTML of the e-mail with sample data, shown in an iframe. */
    public function preview(Request $request, string $template): Response
    {
        abort_unless(EmailTemplates::exists($template), 404);

        $mail = new TemplatedMail(
            $template,
            EmailTemplates::sample($template),
            EmailTemplates::definitions()[$template]['action'] ? url('/') : null,
            null,
            $request->query('subject'),
            $request->query('body'),
        );

        return response($mail->render())->header('X-Frame-Options', 'SAMEORIGIN');
    }
}
