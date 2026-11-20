<?php

use Illuminate\Support\Facades\Route;
use Raytech\LogViewer\Http\Controllers\RaytechLogViewerController;

$enabled = config('raytech-log-viewer.enabled', true);

if ($enabled) {
    $prefix = config('raytech-log-viewer.route.prefix', 'log-viewer');
    $domain = config('raytech-log-viewer.route.domain', null);
    $namePrefix = config('raytech-log-viewer.route.name', 'log-viewer.');

    $routeGroup = Route::prefix($prefix)->name($namePrefix);

    if (!empty($domain)) {
        $routeGroup->domain($domain);
    }

    $routeGroup->group(function () {
        Route::get('/', [RaytechLogViewerController::class, 'index'])->name('index');
        Route::post('/clear', [RaytechLogViewerController::class, 'clear'])->name('clear');
        Route::get('/download', [RaytechLogViewerController::class, 'download'])->name('download');
        Route::get('/raw', [RaytechLogViewerController::class, 'raw'])->name('raw');
        Route::get('/assets/{file}', [RaytechLogViewerController::class, 'asset'])->where('file', '.*')->name('asset');
    });
}
