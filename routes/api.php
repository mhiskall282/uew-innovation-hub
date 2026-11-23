<?php

use App\Http\Controllers\PublicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/publications', [PublicationController::class, 'apiIndex'])->name('api.publications.index');
});
