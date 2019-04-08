<?php
Route::group(['middleware' => ['auth:api', 'bindings']], function() {
    Route::get('admin/spark-package-skeleton/fetch', 'PackageSkeletonController@fetch')->name('package.skeleton.fetch');
    Route::apiResource('admin/spark-package-skeleton', 'PackageSkeletonController');
});
