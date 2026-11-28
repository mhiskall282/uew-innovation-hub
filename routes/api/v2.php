<?php

use App\Http\Controllers\Api\V2;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API version 2 (current)
|--------------------------------------------------------------------------
| Built for long-lived client apps:
|   - cursor pagination (`limit`, `cursor`): constant cost per page, stable
|     under inserts, no COUNT(*) on large tables
|   - `updated_since` on every list for incremental sync
|   - `sort` and `include` from per-endpoint whitelists
|   - string ids, money as { amount: "1000.00", currency: "GHS" }
|   - { "data", "meta", "links" } envelope and machine-readable error codes
|   - ETag / If-None-Match (304) on every successful response
| See docs/06-api-reference/.
*/

Route::get('/', V2\IndexController::class)->name('index');
Route::get('/stats', V2\StatsController::class)->name('stats');
Route::get('/reference', V2\ReferenceController::class)->name('reference');

Route::get('/projects', [V2\ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [V2\ProjectController::class, 'show'])->name('projects.show');
Route::get('/publications', [V2\PublicationController::class, 'index'])->name('publications.index');
Route::get('/publications/{publication}', [V2\PublicationController::class, 'show'])->name('publications.show');
Route::get('/news', [V2\NewsController::class, 'index'])->name('news.index');
Route::get('/news/{post}', [V2\NewsController::class, 'show'])->name('news.show');
Route::get('/partners', [V2\PartnerController::class, 'index'])->name('partners.index');
Route::get('/programmes', [V2\ProgrammeController::class, 'index'])->name('programmes.index');
Route::get('/programmes/{program}', [V2\ProgrammeController::class, 'show'])->name('programmes.show');
Route::get('/albums', [V2\AlbumController::class, 'index'])->name('albums.index');
Route::get('/albums/{album}', [V2\AlbumController::class, 'show'])->name('albums.show');
Route::get('/community', V2\CommunityController::class)->name('community');
