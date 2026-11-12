<?php

use ProcessMaker\Package\PackageSkeleton\Http\Controllers\Api\DeployProcess;
use ProcessMaker\Package\PackageSkeleton\Http\Controllers\Api\DeployScreen;
use ProcessMaker\Package\PackageSkeleton\Http\Controllers\Api\DeployScript;
use ProcessMaker\Package\PackageSkeleton\Http\Controllers\PackageSkeletonController;

Route::group(['middleware' => ['auth:api', 'bindings']], function () {
    Route::get('admin/package-skeleton/fetch', [PackageSkeletonController::class, 'fetch'])
        ->name('package.skeleton.fetch');
    Route::apiResource('admin/package-skeleton', PackageSkeletonController::class);

    Route::post('package-skeleton/deploy-script', [DeployScript::class, 'deployScript'])
        ->name('package-skeleton.deploy-script');
    Route::get('package-skeleton/script-php', [DeployScript::class, 'getScriptPhp'])
        ->name('package-skeleton.script-php');

    Route::post('package-skeleton/deploy-screen', [DeployScreen::class, 'deployScreen'])
        ->name('package-skeleton.deploy-screen');
    Route::get('package-skeleton/screen-yaml', [DeployScreen::class, 'getScreenYaml'])
        ->name('package-skeleton.screen-yaml');

    Route::post('package-skeleton/deploy-process', [DeployProcess::class, 'deployProcess'])
        ->name('package-skeleton.deploy-process');
    Route::get('package-skeleton/process-bpmn', [DeployProcess::class, 'getProcessBpmn'])
        ->name('package-skeleton.process-bpmn');
});
