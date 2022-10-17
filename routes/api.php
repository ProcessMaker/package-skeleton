<?php

Route::group(['middleware' => ['auth:api', 'bindings']], function () {
    Route::get('admin/package-skeleton/fetch', [PackageSkeletonController::class, 'fetch'])->name('package.skeleton.fetch');
    Route::apiResource('admin/package-skeleton', PackageSkeletonController::class);
});
