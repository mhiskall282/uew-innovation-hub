<?php

declare(strict_types=1);

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\UpdateSettingsRequest;
use App\Services\AuditLogger;
use App\Services\MailSettings;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Site-wide settings. Routes are gated by can:settings.manage (Hub Director). */
class SettingsController extends Controller
{
    public function edit(SettingsService $settings, MailSettings $mail): View
    {
        return view('workspace.settings.edit', [
            'settings' => $settings->all(),
            'mailers' => MailSettings::MAILERS,
            'encryptions' => MailSettings::ENCRYPTIONS,
            'hasMailPassword' => $mail->hasPassword(),
            'system' => [
                'Environment' => app()->environment(),
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Database' => config('database.default'),
                'Cache store' => config('cache.default'),
                'Session driver' => config('session.driver'),
                'Queue' => config('queue.default'),
                'Mail transport' => config('mail.default'),
                'Documents disk' => config('hub.documents_disk'),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $settings->update($request->validated());

        return redirect()->route('workspace.settings.edit')->with('status', 'Settings saved. The public site now uses them.');
    }

    public function updateMail(Request $request, MailSettings $mail): RedirectResponse
    {
        $data = $request->validate([
            'mail_mailer' => ['required', Rule::in(array_keys(MailSettings::MAILERS))],
            'mail_host' => ['nullable', 'required_if:mail_mailer,smtp', 'string', 'max:255'],
            'mail_port' => ['nullable', 'required_if:mail_mailer,smtp', 'integer', 'between:1,65535'],
            'mail_encryption' => ['nullable', Rule::in(array_keys(MailSettings::ENCRYPTIONS))],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'clear_password' => ['nullable', 'boolean'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
        ]);
        $data['clear_password'] = $request->boolean('clear_password');

        $mail->save($data);

        return redirect()->route('workspace.settings.edit', ['tab' => 'mail'])->with('status', 'E-mail settings saved. Send a test e-mail to check them.');
    }

    public function testMail(Request $request, MailSettings $mail, AuditLogger $audit): RedirectResponse
    {
        $error = $mail->sendTest($request->user());
        $audit->record('settings.mail_tested', $error ? 'Test e-mail failed' : "Test e-mail sent to {$request->user()->email}");

        return redirect()->route('workspace.settings.edit', ['tab' => 'mail'])->with(
            $error ? 'mail_error' : 'status',
            $error ? 'The test e-mail could not be sent: '.Str::limit($error, 300) : "Test e-mail sent to {$request->user()->email}. Check the inbox (and spam folder).",
        );
    }
}
