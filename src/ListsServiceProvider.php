<?php

namespace Zak\Lists;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zak\Lists\Commands\ComponentCommand;

class ListsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('lists')
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets()
            ->hasCommand(ComponentCommand::class)
            ->hasMigration('create_option_table')
            ->publishesServiceProvider('ListsServiceProvider')
            ->hasRoute('lists')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publish('views')
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations();
                //->copyAndRegisterServiceProviderInApp();
            });
        //->hasMigration('create_lists_table')
        //->hasCommand(ListsCommand::class);
    }
}
