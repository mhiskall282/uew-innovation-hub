<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public REST API, version 1
|--------------------------------------------------------------------------
| Read-only access to public data: impact statistics, projects, publications,
| news and partners. Rate limited to 60 requests per minute per IP.
| Documented in docs/api.md and docs/api/openapi.yaml.
*/

Route::prefix('v1')->middleware('throttle:api')->name('api.v1.')->group(function () {
    Route::get('/', V1\IndexController::class)->name('index');
    Route::get('/stats', V1\StatsController::class)->name('stats');

    Route::get('/projects', [V1\ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [V1\ProjectController::class, 'show'])->name('projects.show');

    Route::get('/publications', [V1\PublicationController::class, 'index'])->name('publications.index');
    Route::get('/publications/{publication}', [V1\PublicationController::class, 'show'])->name('publications.show');

    Route::get('/news', [V1\NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{post}', [V1\NewsController::class, 'show'])->name('news.show');

    Route::get('/partners', [V1\PartnerController::class, 'index'])->name('partners.index');
});
