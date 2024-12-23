<?php

use App\Models\Classificator;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Text;

return Component::init([
    'model' => Classificator::class,
    'singleLabel' => 'Классификация',
    'label' => 'Классификации',
    'fields' => [
        ID::make('ID', 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Text::make('Название', 'name')->sortable()->defaultAction(),
    ],

]);
