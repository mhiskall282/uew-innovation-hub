<?php

use App\Http\Controllers\Api\VersionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Open data API
|--------------------------------------------------------------------------
| Read-only access to public data. Each version lives in its own route file
| and controller namespace, and keeps its URL and response shape for its
| whole life (see config/api.php for status, deprecation and sunset dates).
|
|   /api       list of versions
|   /api/v1    routes/api/v1.php  (App\Http\Controllers\Api\V1)
|   /api/v2    routes/api/v2.php  (App\Http\Controllers\Api\V2)  current
|
| All versions share the `api` rate limiter (60 requests per minute per IP
| by default). Documented in docs/06-api-reference/.
*/

Route::get('/', VersionsController::class)->middleware('throttle:api')->name('api.versions');

Route::prefix('v1')
    ->middleware(['api.version:v1', 'throttle:api'])
    ->name('api.v1.')
    ->group(base_path('routes/api/v1.php'));

Route::prefix('v2')
    ->middleware(['api.version:v2', 'throttle:api'])
    ->name('api.v2.')
    ->group(base_path('routes/api/v2.php'));
