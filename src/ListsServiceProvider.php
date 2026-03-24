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

    public function packageBooted(): void
    {
        $this->validateConfig();

        // spatie/laravel-package-tools registers translations under the 'lists::' namespace.
        // We also add the path without a namespace so the short form __('lists.key') works
        // in any host application without requiring published translation files.
        $this->callAfterResolving('translator', function ($translator) {
            $translator->getLoader()->addPath($this->package->basePath('/../resources/lang'));
        });
    }

    /**
     * Проверяет критические настройки конфига при старте приложения.
     * Даёт ранний и понятный сигнал о неверной конфигурации.
     *
     * @throws \RuntimeException
     */
    private function validateConfig(): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $path = config('lists.path');

        if (empty($path)) {
            throw new \RuntimeException(
                'Zak/Lists: "lists.path" is not configured. Publish the config with: php artisan vendor:publish --tag="lists-config"'
            );
        }

        $defaultLength = config('lists.default_length');

        if (! is_int($defaultLength) || $defaultLength <= 0) {
            throw new \RuntimeException(
                'Zak/Lists: "lists.default_length" must be a positive integer. Got: '.var_export($defaultLength, true)
            );
        }

        $maxExportRows = config('lists.max_export_rows', 0);

        if (! is_int($maxExportRows) || $maxExportRows < 0) {
            throw new \RuntimeException(
                'Zak/Lists: "lists.max_export_rows" must be a non-negative integer. Got: '.var_export($maxExportRows, true)
            );
        }

        $importClass = config('lists.import_class');

        if (! empty($importClass) && ! class_exists($importClass)) {
            throw new \RuntimeException(
                'Zak/Lists: "lists.import_class" references a class that does not exist: '.$importClass
            );
        }
    }
}
