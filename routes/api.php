<?php
Route::group(['middleware' => ['auth:api', 'bindings']], function() {
    Route::get('admin/bpm-package-skeleton/fetch', 'PackageSkeletonController@fetch')->name('package.skeleton.fetch');
    Route::apiResource('admin/bpm-package-skeleton', 'PackageSkeletonController');
});
