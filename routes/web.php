<?php

Route::group(['middleware' => ['auth']], function () {
    Route::get('admin/spark-package-skeleton', 'PackageSkeletonController@index')->name('package.skeleton.index');
});