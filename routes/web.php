<?php

Route::group(['middleware' => ['auth']], function () {
    Route::get('admin/bpm-package-skeleton', 'PackageSkeletonController@index')->name('package.skeleton.index');
});