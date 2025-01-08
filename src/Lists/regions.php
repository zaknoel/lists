<?php

use App\Models\Region;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Text;

return Component::init([
    'model' => Region::class,
    'singleLabel' => 'Регион',
    'label' => 'Регионы',
    'fields' => [
        ID::make('ID', 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make('Активность', 'active')->sortable()->filterable()->default(true)->width(12),
        Text::make('Название', 'name')->sortable()->defaultAction(),
        Number::make('Сортировка', 'sort')->sortable()->default(500),

    ],

]);
