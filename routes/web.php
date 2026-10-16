<?php

use App\Http\Controllers\IeecDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IeecDashboardController::class, 'index'])->name('dashboard');
Route::get('/copilot', [IeecDashboardController::class, 'index'])->name('copilot');
Route::post('/config', [IeecDashboardController::class, 'updateConfig'])->name('config.update');
Route::post('/upload', [IeecDashboardController::class, 'uploadCsv'])->name('csv.upload')->middleware('throttle:30,1');
Route::post('/chat', [IeecDashboardController::class, 'chat'])->name('copilot.chat')->middleware('throttle:30,1');
Route::get('/api/stream-iot', [IeecDashboardController::class, 'streamIot'])->name('iot.stream');
Route::get('/export-audit', [IeecDashboardController::class, 'exportReport'])->name('audit.export');
