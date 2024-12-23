<?php

use App\Models\Category;
use App\Models\Product;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Text;

return Component::init([
    'model' => Product::class,
    'singleLabel' => 'товар',
    'label' => 'Товары',
    'fields' => [
        ID::make('ID', 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make('Активность', 'active')->sortable()->filterable()->default(true),
        Text::make('Название', 'name')->sortable()->defaultAction(),
        Text::make('Другие название', 'alt_name')->multiple(),
        Text::make('Производитель', 'brand')->sortable(),
        Relation::make('Категория', 'category_id')
            ->model(Category::class)
            ->filter(['active', '=', true])
            ->field('name'),
        Number::make('Цена', 'price')->sortable()->filterable(),
        Number::make('Сортировка', 'sort')->sortable()->default(500),
    ],

]);
