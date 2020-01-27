<?php

Route::group(['middleware' => ['auth']], function () {
    Route::get('admin/package-skeleton', 'PackageSkeletonController@index')->name('package.skeleton.index');
});
