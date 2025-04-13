<?php

use App\Http\Controllers\DriverApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/driver/application', [DriverApplicationController::class, 'showApplicationForm'])
    ->name('driver.application');

Route::post('/driver/application', [DriverApplicationController::class, 'submitApplication'])
    ->name('driver.application.submit');

Route::get('/driver/application/status', [DriverApplicationController::class, 'checkApplicationStatus'])
    ->name('driver.application.status');

Route::get('/driver/application/pending', [DriverApplicationController::class, 'pendingApplication'])
    ->name('driver.application.pending');

