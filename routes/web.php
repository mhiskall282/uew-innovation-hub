<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileStreamController;
use App\Http\Controllers\GrantApplicationController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Institutional Showcase Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Public News & Blog CMS
Route::get('/news-and-blogs', [PostController::class, 'index'])->name('posts.index');
Route::get('/news-and-blogs/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Public Online Grant Application
Route::get('/apply', [GrantApplicationController::class, 'create'])->name('grants.apply');
Route::post('/apply', [GrantApplicationController::class, 'store'])->name('grants.store')->middleware('throttle:10,1');
Route::get('/apply/success', [GrantApplicationController::class, 'success'])->name('grants.success');

// Authentication Subsystem
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Cockpit Telemetry Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Projects & Initiatives Subsystem
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/export/csv', [ProjectController::class, 'export'])->name('projects.export');

Route::middleware(['auth', 'role:officer'])->group(function () {
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:slug}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project:slug}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.destroy');
});

Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

// Publications & Research Papers
Route::get('/publications', [PublicationController::class, 'index'])->name('publications.index');

// Partner Organizations
Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store')->middleware(['auth', 'role:officer']);

// Cockpit Administration & Management
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Staff & Access Control (super admin only)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/activity-logs', [UserController::class, 'activityLogs'])->name('activity-logs');
    });

    // CMS News & Blogs
    Route::middleware('role:officer,intern')->group(function () {
        Route::get('/posts', [PostController::class, 'adminIndex'])->name('posts.index');
        Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    });

    // Grant Applications Review
    Route::middleware('role:officer,reviewer')->group(function () {
        Route::get('/grants', [GrantApplicationController::class, 'index'])->name('grants.index');
        Route::get('/grants/{application}', [GrantApplicationController::class, 'show'])->name('grants.show');
        Route::post('/grants/{application}/status', [GrantApplicationController::class, 'updateStatus'])->name('grants.status');
    });
});
// Secure Document Streaming (protected against path traversal)
Route::get('/files/{type}/{filename}', [FileStreamController::class, 'stream'])
    ->where('filename', '.*')
    ->name('files.stream');

