<?php

use App\Models\Category;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Text;

return Component::init([
    'model' => Category::class,
    'singleLabel' => 'Категория',
    'label' => 'Категории',
    'fields' => [
        ID::make('ID', 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make('Активность', 'active')->sortable()->filterable()->width(12)->default(true),
        Text::make('Название', 'name')->sortable()->defaultAction(),
        Number::make('Сортировка', 'sort')->sortable()->width(12)->default(500),
    ],

]);
