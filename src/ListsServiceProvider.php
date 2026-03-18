<?php

namespace Zak\Lists;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Zak\Lists\Commands\ComponentCommand;
use Zak\Lists\Commands\MakeFieldCommand;
use Zak\Lists\Contracts\AuthorizationContract;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Contracts\FieldServiceContract;
use Zak\Lists\Contracts\QueryContract;
use Zak\Lists\Services\AuthorizationService;
use Zak\Lists\Services\ComponentLoader;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Services\FieldService;
use Zak\Lists\Services\QueryService;

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
            ->hasTranslations()
            ->hasCommands(ComponentCommand::class, MakeFieldCommand::class)
            ->hasMigration('create_option_table')
            ->publishesServiceProvider('ListsServiceProvider')
            ->hasRoute('lists')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publish('views')
                    ->publishConfigFile()
                    ->publishAssets()
                    ->publishMigrations()
                    ->publish('translations');
            });
    }

    public function packageRegistered(): void
    {
        // Singleton для кэширования загруженных компонентов в рамках одного запроса
        $this->app->singleton(ComponentLoaderContract::class, ComponentLoader::class);

        // Сервисы регистрируем как синглтоны
        $this->app->singleton(AuthorizationContract::class, AuthorizationService::class);
        $this->app->singleton(QueryContract::class, QueryService::class);
        $this->app->singleton(FieldServiceContract::class, FieldService::class);
        $this->app->singleton(ExportService::class, ExportService::class);

        // Алиасы для удобного использования через app()
        $this->app->alias(ComponentLoaderContract::class, 'lists.loader');
    }
}
