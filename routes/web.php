<?php

use App\Http\Controllers\Auth;
use App\Http\Controllers\Portal;
use App\Http\Controllers\Site;
use App\Http\Controllers\Workspace;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::name('site.')->group(function () {
    Route::get('/', Site\HomeController::class)->name('home');

    Route::get('/programmes', [Site\ProgramController::class, 'index'])->name('programs.index');
    Route::get('/programmes/{program}', [Site\ProgramController::class, 'show'])->name('programs.show');

    Route::get('/projects', [Site\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [Site\ProjectController::class, 'show'])->name('projects.show');

    Route::get('/publications', [Site\PublicationController::class, 'index'])->name('publications.index');

    Route::get('/news', [Site\NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{post}', [Site\NewsController::class, 'show'])->name('news.show');

    Route::get('/partners', [Site\PartnerController::class, 'index'])->name('partners.index');

    Route::get('/contact', [Site\ContactController::class, 'create'])->name('contact');
    Route::post('/contact', [Site\ContactController::class, 'store'])->middleware('throttle:contact-messages')->name('contact.store');

    Route::get('/documents/projects/{project}/report', [Site\DocumentController::class, 'projectReport'])->name('documents.project-report');
    Route::get('/documents/publications/{publication}', [Site\DocumentController::class, 'publication'])->name('documents.publication');

    Route::get('/unsubscribe/{user}', Site\UnsubscribeController::class)->middleware('signed')->name('unsubscribe');
});

// Old URLs.
Route::permanentRedirect('/news-and-blogs', '/news');
Route::redirect('/apply', '/programmes');
Route::redirect('/track', '/portal');

/*
|--------------------------------------------------------------------------
| Accounts: sign-up, e-mail code verification, sign-in, password reset
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/sign-in', [Auth\SessionController::class, 'create'])->name('login');
    Route::post('/sign-in', [Auth\SessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');

    Route::get('/sign-up', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/sign-up', [Auth\RegisterController::class, 'store'])->middleware('throttle:registrations')->name('register.store');

    Route::get('/forgot-password', [Auth\PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [Auth\PasswordResetController::class, 'send'])->middleware('throttle:password-codes')->name('password.email');
    Route::get('/reset-password', [Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

// Common sign-in / sign-up URLs people type or bookmark.
Route::permanentRedirect('/workspace/sign-in', '/sign-in');
Route::permanentRedirect('/login', '/sign-in');
Route::permanentRedirect('/signin', '/sign-in');
Route::permanentRedirect('/register', '/sign-up');
Route::permanentRedirect('/signup', '/sign-up');
Route::permanentRedirect('/admin', '/workspace');

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/sign-out', [Auth\SessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', [Auth\EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/verify-email', [Auth\EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/verify-email/resend', [Auth\EmailVerificationController::class, 'resend'])->middleware('throttle:5,10')->name('verification.resend');

    Route::middleware('verified.email')->group(function () {
        Route::get('/onboarding', [Portal\OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding', [Portal\OnboardingController::class, 'store'])->name('onboarding.store');
    });
});

/*
|--------------------------------------------------------------------------
| Member portal
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'verified.email', 'onboarded'])->group(function () {
    Route::get('/programmes/{program}/apply', [Portal\ApplicationController::class, 'create'])->name('portal.apply');
    Route::post('/programmes/{program}/apply', [Portal\ApplicationController::class, 'store'])->middleware('throttle:30,1')->name('portal.apply.store');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('/applications', [Portal\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [Portal\ApplicationController::class, 'show'])->name('applications.show');
        Route::post('/applications/{application}/withdraw', [Portal\ApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::get('/applications/{application}/files/{key}', [Portal\ApplicationController::class, 'file'])->name('applications.file');
        Route::get('/profile', [Portal\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [Portal\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [Portal\ProfileController::class, 'password'])->name('profile.password');
    });
});

/*
|--------------------------------------------------------------------------
| Staff workspace
|--------------------------------------------------------------------------
| Every route needs an active, verified staff account with workspace.access,
| plus the permission named on its group. Policies re-check each action.
*/

Route::prefix('workspace')->middleware(['auth', 'active', 'verified.email', 'can:workspace.access'])->name('workspace.')->group(function () {
    Route::get('/', Workspace\DashboardController::class)->name('dashboard');

    // Programmes and applications
    Route::middleware('can:programs.manage')->group(function () {
        Route::get('/programmes', [Workspace\ProgramController::class, 'index'])->name('programs.index');
        Route::get('/programmes/create', [Workspace\ProgramController::class, 'create'])->name('programs.create');
        Route::post('/programmes', [Workspace\ProgramController::class, 'store'])->name('programs.store');
        Route::get('/programmes/{program}/edit', [Workspace\ProgramController::class, 'edit'])->name('programs.edit');
        Route::put('/programmes/{program}', [Workspace\ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programmes/{program}', [Workspace\ProgramController::class, 'destroy'])->name('programs.destroy');
        Route::post('/programmes/{program}/announce', [Workspace\ProgramController::class, 'announce'])->name('programs.announce');
    });

    Route::middleware('can:applications.review')->group(function () {
        Route::get('/programmes/{program}', [Workspace\ProgramController::class, 'show'])->name('programs.show');
        Route::get('/programmes/{program}/export', [Workspace\ProgramController::class, 'export'])->name('programs.export');
        Route::post('/programmes/{program}/applications/bulk', [Workspace\ApplicationController::class, 'bulk'])->name('applications.bulk');
        Route::get('/applications', [Workspace\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [Workspace\ApplicationController::class, 'show'])->name('applications.show');
        Route::put('/applications/{application}', [Workspace\ApplicationController::class, 'update'])->name('applications.update');
        Route::get('/applications/{application}/files/{key}', [Workspace\ApplicationController::class, 'file'])->name('applications.file');
    });

    // People
    Route::middleware('can:members.manage')->group(function () {
        Route::get('/members', [Workspace\MemberController::class, 'index'])->name('members.index');
        Route::get('/members/export', [Workspace\MemberController::class, 'export'])->name('members.export');
        Route::get('/members/{member}', [Workspace\MemberController::class, 'show'])->name('members.show');
        Route::patch('/members/{member}/toggle', [Workspace\MemberController::class, 'toggle'])->name('members.toggle');
    });

    Route::middleware('can:broadcasts.send')->group(function () {
        Route::get('/broadcasts/audience', [Workspace\BroadcastController::class, 'audience'])->name('broadcasts.audience');
        Route::resource('broadcasts', Workspace\BroadcastController::class);
    });

    // Portfolio
    Route::get('/projects/export', [Workspace\ProjectController::class, 'export'])->name('projects.export');
    Route::get('/projects/{project}/report', [Workspace\ProjectController::class, 'report'])->name('projects.report');
    Route::resource('projects', Workspace\ProjectController::class);

    Route::get('/grants', [Workspace\GrantApplicationController::class, 'index'])->name('grants.index');
    Route::get('/grants/{application:reference_no}', [Workspace\GrantApplicationController::class, 'show'])->name('grants.show');
    Route::put('/grants/{application:reference_no}', [Workspace\GrantApplicationController::class, 'update'])->name('grants.update');
    Route::get('/grants/{application:reference_no}/proposal', [Workspace\GrantApplicationController::class, 'proposal'])->name('grants.proposal');

    Route::resource('posts', Workspace\PostController::class)->except('show');
    Route::resource('publications', Workspace\PublicationController::class)->except('show');
    Route::resource('organizations', Workspace\OrganizationController::class)->except('show');

    Route::middleware('can:pages.manage')->group(function () {
        Route::resource('pages', Workspace\PageController::class)->except('show');
    });

    Route::middleware('can:messages.manage')->group(function () {
        Route::get('/messages', [Workspace\ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [Workspace\ContactMessageController::class, 'show'])->name('messages.show');
        Route::patch('/messages/{message}', [Workspace\ContactMessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [Workspace\ContactMessageController::class, 'destroy'])->name('messages.destroy');
    });

    Route::get('/reports', Workspace\ReportController::class)->middleware('can:reports.view')->name('reports.index');

    // Administration
    Route::patch('/users/{user}/toggle', [Workspace\UserController::class, 'toggle'])->name('users.toggle');
    Route::resource('users', Workspace\UserController::class)->except('show');

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('/settings', [Workspace\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Workspace\SettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/mail', [Workspace\SettingsController::class, 'updateMail'])->name('settings.mail');
        Route::post('/settings/mail/test', [Workspace\SettingsController::class, 'testMail'])->middleware('throttle:5,1')->name('settings.mail.test');

        Route::get('/email-templates', [Workspace\EmailTemplateController::class, 'index'])->name('templates.index');
        Route::get('/email-templates/{template}', [Workspace\EmailTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/email-templates/{template}', [Workspace\EmailTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/email-templates/{template}', [Workspace\EmailTemplateController::class, 'reset'])->name('templates.reset');
        Route::get('/email-templates/{template}/preview', [Workspace\EmailTemplateController::class, 'preview'])->name('templates.preview');
    });

    Route::get('/audit', Workspace\AuditLogController::class)->middleware('can:audit.view')->name('audit.index');

    Route::get('/account', [Workspace\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/account', [Workspace\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/account/password', [Workspace\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/account/sessions', [Workspace\ProfileController::class, 'destroyOtherSessions'])->name('profile.sessions.destroy');
});

/*
|--------------------------------------------------------------------------
| CMS pages: /about, /faq, /privacy ... (registered last so real routes win)
|--------------------------------------------------------------------------
*/

Route::get('/{page}', Site\PageController::class)
    ->where('page', '^(?!up$|api$|workspace$|portal$)[a-z0-9-]+$')
    ->name('site.page');
