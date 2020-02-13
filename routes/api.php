<?php
Route::group(['middleware' => ['auth:api', 'bindings']], function() {
    Route::get('admin/package-skeleton/fetch', 'PackageSkeletonController@fetch')->name('package.skeleton.fetch');
    Route::apiResource('admin/package-skeleton', 'PackageSkeletonController');
});
