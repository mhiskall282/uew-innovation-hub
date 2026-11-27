<?php

/**
 * Live e-mail test: runs every flow that sends mail, through the SMTP settings
 * in .env, against a throwaway copy of the dev database. MAIL_ALWAYS_TO keeps
 * every message in the tester's inbox. Prints one row per message with the
 * SMTP server's message id, or the error.
 *
 *   php scripts/email-flow-test.php
 */

use App\Enums\ApplicationStatus;
use App\Enums\GrantStatus;
use App\Enums\Role;
use App\Models\Broadcast;
use App\Models\GrantApplication;
use App\Models\Program;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\ApplicationService;
use App\Services\BroadcastService;
use App\Services\ContactMessageService;
use App\Services\GrantApplicationService;
use App\Services\MailSettings;
use App\Services\MemberService;
use App\Services\UserService;
use App\Services\VerificationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;

$root = dirname(__DIR__);
$copy = $root.'/storage/email-test.sqlite';
copy($root.'/database/database.sqlite', $copy);
putenv("DB_DATABASE={$copy}");
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $copy;

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config(['queue.default' => 'sync']); // deliver queued mail now so failures show up here

$log = [];
$step = '';
$lastCode = null;
Event::listen(MessageSending::class, function (MessageSending $e) use (&$lastCode) {
    if (preg_match('/\b(\d{3})\s?(\d{3})\b/', (string) $e->message->getTextBody(), $m)) {
        $lastCode = $m[1].$m[2];
    }
});
Event::listen(MessageSent::class, function (MessageSent $e) use (&$log, &$step) {
    $log[] = [$step, $e->message->getSubject(), implode(',', array_map(fn ($a) => $a->getAddress(), $e->message->getTo())), $e->sent->getMessageId()];
});

$run = function (string $name, callable $fn) use (&$step, &$log) {
    $step = $name;
    $before = count($log);
    try {
        $fn();
        if (count($log) === $before) {
            $log[] = [$name, '(no e-mail sent)', '', ''];
        }
    } catch (Throwable $e) {
        $log[] = [$name, 'FAILED', '', get_class($e).': '.$e->getMessage()];
    }
};

$inbox = config('mail.always_to') ?: config('mail.from.address');
[$local, $domain] = explode('@', $inbox);
$memberEmail = "{$local}+uewtest".date('His')."@{$domain}";
$admin = User::where('role', Role::SuperAdmin)->where('is_active', true)->firstOrFail();
$reviewer = User::where('role', Role::Reviewer)->first() ?? $admin;
$member = null;

$run('1. SMTP test e-mail (Site settings > Send test)', fn () => app(MailSettings::class)->sendTest($admin));

$run('2. Sign-up: verification code', function () use (&$member, $memberEmail) {
    $member = app(MemberService::class)->register([
        'name' => 'Email Test Member', 'email' => $memberEmail, 'password' => 'Email-Test-2026x',
        'phone' => '+233 24 000 0000', 'gender' => 'prefer_not', 'date_of_birth' => '2000-01-01', 'region' => 'Central',
    ]);
});

$run('3. Verify e-mail with the code: welcome e-mail', function () use (&$member, &$lastCode) {
    app(MemberService::class)->verifyEmail($member, (string) $lastCode);
});

$run('4. Forgot password: reset code', function () use (&$member) {
    app(VerificationService::class)->issue($member->fresh(), VerificationCode::RESET_PASSWORD);
});

$run('5. Reset password with the code: "password changed" notice', function () use (&$member, &$lastCode) {
    app(VerificationService::class)->verify($member, VerificationCode::RESET_PASSWORD, (string) $lastCode);
    app(UserService::class)->resetPassword($member->fresh(), 'Email-Test-2026y');
});

$application = null;
$run('6. Submit application: applicant receipt + staff alerts', function () use (&$member, &$application) {
    $member = app(MemberService::class)->completeOnboarding($member->fresh(), [
        'name' => 'Email Test Member', 'phone' => '+233 24 000 0000', 'gender' => 'prefer_not', 'date_of_birth' => '2000-01-01',
        'region' => 'Central', 'affiliation' => 'student', 'education_level' => 'undergraduate', 'interests' => ['STEM'], 'marketing_opt_in' => true,
    ]);
    $program = Program::open()->get()->first(fn ($p) => collect($p->fields())->where('type', 'file')->where('required', true)->isEmpty());
    $answers = collect($program->fields())->reject(fn ($f) => $f['type'] === 'file')
        ->mapWithKeys(fn ($f) => [$f['key'] => match ($f['type']) {
            'number' => '3', 'checkboxes' => [$f['options'][0] ?? 'x'], 'select', 'radio' => $f['options'][0] ?? 'x',
            'yes_no' => 'yes', 'email' => 'test@example.com', 'url' => 'https://example.com', 'date' => '2026-12-01', default => 'E-mail flow test answer',
        }])->all();
    $application = app(ApplicationService::class)->save($program, $member, $answers, [], true);
});

$run('7. Review decision: status e-mail (shortlisted)', function () use (&$application, $reviewer) {
    app(ApplicationService::class)->review($application->fresh(), ['status' => ApplicationStatus::Shortlisted->value, 'notify' => true, 'decision_message' => 'Please prepare a 5-minute pitch.'], $reviewer);
});

$run('8. Contact form: staff alert', function () {
    app(ContactMessageService::class)->receive(['name' => 'E-mail Test', 'email' => 'visitor@example.com', 'subject' => 'Partnership enquiry', 'message' => 'This is an automated e-mail flow test.'], '127.0.0.1');
});

$run('9. Broadcast to opted-in members', function () use ($memberEmail) {
    $broadcast = new Broadcast(['subject' => 'E-mail test broadcast', 'body' => "Hello {{ first_name }},\n\nThis is a **test broadcast** from the Hub.", 'audience' => ['interests' => ['STEM'], 'affiliations' => ['student']]]);
    $broadcast->created_by = User::where('role', Role::SuperAdmin)->value('id');
    $broadcast->save();
    // Only the test member: everyone else in the copy is opted out first.
    User::members()->where('email', '!=', $memberEmail)->update(['marketing_opt_in' => false]);
    app(BroadcastService::class)->send($broadcast);
});

$run('10. Legacy grant decision e-mail', function () use ($reviewer) {
    $grant = GrantApplication::query()->first();
    if ($grant) {
        app(GrantApplicationService::class)->review($grant, GrantStatus::UnderReview, 'E-mail test', $reviewer, true);
    }
});

$run('11. Staff password change notice', function () use ($admin) {
    app(UserService::class)->changeOwnPassword($admin->fresh(), 'Admin-Mail-Test-2026');
});

// Report
printf("%-58s %-62s %s\n", 'Flow', 'Subject', 'Result');
$ok = 0;
$failed = 0;
foreach ($log as [$flow, $subject, $to, $result]) {
    $isFail = $subject === 'FAILED' || $subject === '(no e-mail sent)';
    $isFail ? $failed++ : $ok++;
    printf("%-58s %-62s %s\n", mb_strimwidth($flow, 0, 57), mb_strimwidth($subject, 0, 61), $isFail ? $result ?: 'no message' : 'sent to '.$to.' id='.mb_strimwidth((string) $result, 0, 40));
}
echo PHP_EOL."Sent: {$ok}   Problems: {$failed}   Mailer: ".config('mail.default').' via '.config('mail.mailers.smtp.host').PHP_EOL;

app('db')->disconnect();
@unlink($copy);
exit($failed ? 1 : 0);
