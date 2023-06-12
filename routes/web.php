<?php

Route::group(['middleware' => ['auth']], function () {
    Route::get('admin/package-ai', [PackageAiController::class, 'index'])->name('package.skeleton.index');
    Route::get('package-ai', [PackageAiController::class, 'index'])->name('package.skeleton.tab.index');
});
