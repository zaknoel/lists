<?php

use App\Models\Category;
use App\Models\Dist;
use App\Models\District;
use App\Models\Ppt;
use App\Models\Product;
use App\Models\Region;
use App\Models\Test;
use App\Zak\Component\Action;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\Date;
use App\Zak\Component\Fields\Email;
use App\Zak\Component\Fields\File;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Image;
use App\Zak\Component\Fields\Location;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Select;
use App\Zak\Component\Fields\Text;

return Component::init([
    "model" => Product::class,
    "singleLabel" => "товар",
    "label" => "Товары",
    "fields" => [
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make("Активность", 'active')->sortable()->filterable()->default(true),
        Text::make("Название", "name")->sortable()->defaultAction(),
        Text::make("Другие название", "alt_name")->multiple(),
        Text::make("Производитель", "brand")->sortable(),
        Relation::make('Категория', "category_id")
            ->model(Category::class)
            ->filter(["active", "=", true])
            ->field('name'),
        Number::make("Цена", "price")->sortable()->filterable(),
        Number::make("Сортировка", "sort")->sortable()->default(500),
    ],

]);
