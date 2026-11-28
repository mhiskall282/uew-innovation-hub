<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API version 1 (supported)
|--------------------------------------------------------------------------
| Page-numbered lists and the { "success": true, "data": ... } envelope.
| Frozen: no breaking changes. New features go to v2.
*/

Route::get('/', V1\IndexController::class)->name('index');
Route::get('/stats', V1\StatsController::class)->name('stats');
Route::get('/projects', [V1\ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [V1\ProjectController::class, 'show'])->name('projects.show');
Route::get('/publications', [V1\PublicationController::class, 'index'])->name('publications.index');
Route::get('/publications/{publication}', [V1\PublicationController::class, 'show'])->name('publications.show');
Route::get('/news', [V1\NewsController::class, 'index'])->name('news.index');
Route::get('/news/{post}', [V1\NewsController::class, 'show'])->name('news.show');
Route::get('/partners', [V1\PartnerController::class, 'index'])->name('partners.index');
Route::get('/programmes', [V1\ProgramController::class, 'index'])->name('programmes.index');
Route::get('/programmes/{program}', [V1\ProgramController::class, 'show'])->name('programmes.show');
Route::get('/community', V1\CommunityController::class)->name('community');
