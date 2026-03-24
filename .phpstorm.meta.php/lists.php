<?php

declare(strict_types=1);

namespace PHPSTORM_META {
    override(\app(0), map([
        'lists.loader' => \Zak\Lists\Services\ComponentLoader::class,
        \Zak\Lists\Contracts\ComponentLoaderContract::class => \Zak\Lists\Services\ComponentLoader::class,
        \Zak\Lists\Contracts\AuthorizationContract::class => \Zak\Lists\Services\AuthorizationService::class,
        \Zak\Lists\Contracts\FieldServiceContract::class => \Zak\Lists\Services\FieldService::class,
        \Zak\Lists\Contracts\QueryContract::class => \Zak\Lists\Services\QueryService::class,
        \Zak\Lists\Services\ExportService::class => \Zak\Lists\Services\ExportService::class,
    ]));

    override(\resolve(0), map([
        \Zak\Lists\Contracts\ComponentLoaderContract::class => \Zak\Lists\Services\ComponentLoader::class,
        \Zak\Lists\Contracts\AuthorizationContract::class => \Zak\Lists\Services\AuthorizationService::class,
        \Zak\Lists\Contracts\FieldServiceContract::class => \Zak\Lists\Services\FieldService::class,
        \Zak\Lists\Contracts\QueryContract::class => \Zak\Lists\Services\QueryService::class,
        \Zak\Lists\Services\ExportService::class => \Zak\Lists\Services\ExportService::class,
    ]));
}

