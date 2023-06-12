<?php

namespace ProcessMaker\Package\PackageSkeleton\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use ProcessMaker\Console\PackageInstallCommand;

class Install extends PackageInstallCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '{package-name}:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install {package-name-human} Package';

    /**
     * Publish assets
     * @return void
     */
    public function publishAssets()
    {
        $this->info('Publishing assets');
        Artisan::call('vendor:publish', [
            '--tag' => '{package-name}',
            '--force' => true,
        ]);
    }

    public function preinstall()
    {
        $this->publishAssets();
    }

    public function install()
    {
    }

    public function postinstall()
    {
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();
        $this->info('{package-name-human} has been installed');

    }
}