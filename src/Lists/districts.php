<?php

use App\Models\Dist;
use App\Models\District;
use App\Models\Region;
use App\Models\Test;
use App\Models\User;
use App\Zak\Component\Action;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\Email;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Image;
use App\Zak\Component\Fields\Location;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Select;
use App\Zak\Component\Fields\Text;
use \App\Zak\Component\Fields\File;
use   \App\Zak\Component\Fields\Date;
return Component::init([
    "model" => District::class,
    "singleLabel"=>"Район",
    "label"=>"Районы",
    "fields" => [
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make("Активность", 'active')->sortable()->filterable()->default(true)->width(12),
        Text::make("Название", "name")->sortable()->defaultAction(),
        Relation::make("Регион", 'region_id')
            ->model(Region::class)
            ->filter(["active", "=", true])
            ->field('name'),
        Number::make("Сортировка", "sort")->sortable()->default(500),

    ],

]);