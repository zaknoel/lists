<?php

use App\Models\Ppt;
use App\Models\Product;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\File;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Text;

return Component::init([
    'model' => Ppt::class,
    'singleLabel' => 'презентация',
    'label' => 'Презентации',
    'fields' => [
        ID::make('ID', 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make('Активность', 'active')->sortable()->filterable()->default(true),
        Text::make('Название', 'name')->sortable()->defaultAction(),
        File::make('Файл', 'file')->path('ppts'),
        BelongToMany::make('Продукты', 'products')
            ->model(Product::class)
            ->filter(['active', '=', true])
            ->field('name'),
        Number::make('Сортировка', 'sort')->sortable()->default(500),

    ],

]);
