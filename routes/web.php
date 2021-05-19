<?php

use Illuminate\Support\Facades\Route;
use RahulHaque\Filepond\Http\Controllers\FilepondController;

Route::group(['middleware' => config('filepond.middleware', ['web', 'auth'])], function () {
    Route::post(config('filepond.server.process', '/filepond'), [config('filepond.controller', FilepondController::class), 'process'])->name('filepond-process');
    Route::delete(config('filepond.server.revert', '/filepond'), [config('filepond.controller', FilepondController::class), 'revert'])->name('filepond-revert');
});
