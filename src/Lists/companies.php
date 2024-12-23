<?php

use App\Models\Classificator;
use App\Models\Company;
use App\Models\Dist;
use App\Models\District;
use App\Models\Doctor;
use App\Models\Network;
use App\Models\Product;
use App\Models\Region;
use App\Models\Script;
use App\Models\Status;
use App\Models\User;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Location;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Text;

return Component::init([
    "model" => Company::class,
    "singleLabel" => "контрагент",
    "label" => "Контрагенты",
    "fields" => [

        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make("Активность", 'active')->sortable()->filterable()->default(true),
        Text::make("Название", "name")->sortable()->defaultAction()->width(4),
        Text::make("Другие название", "alt_name")->multiple()->width(4)->hideOnIndex(),
        Text::make("Инн", "inn")->filterable()->sortable()->width(4),
        Relation::make('Регион', "region_id")
            ->model(Region::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        Relation::make('Район', "district_id")
            ->model(District::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        Relation::make('Дистрибьютор', "dist_id")
            ->model(Dist::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        Relation::make('Сеть аптек', "network_id")
            ->model(Network::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        Relation::make('Класс', "class_id")
            ->model(Classificator::class)
            ->field('name')
        ,
        Relation::make("RX Мед. пред.", 'rx_user_id')
            ->model(User::class)->field('name')
            ->sortable()->filterable()->filter(["operator"]),
        Relation::make("OTC Мед. пред.", 'ots_user_id')
            ->model(User::class)->field('name')
            ->sortable()->filterable()->filter(["operator"]),
        Relation::make("ДЕРМО Мед. пред.", 'dermo_user_id')
            ->model(User::class)->field('name')
            ->sortable()->filterable()->filter(["operator"]),

        Text::make("Адрес", "address"),
        Text::make("Номер телефона", "phone"),
        Location::make("Локация", 'location'),
        Number::make("Сортировка", "sort")->sortable()->default(500),



    ],
    "onSearchModel" => function ($model) {
        if (isMedPred()) {
            return  $model->where(function ($query) {
                return $query->where('rx_user_id', auth()->user()->id)
                    ->orWhere('ots_user_id', auth()->user()->id)
                    ->orWhere('dermo_user_id', auth()->user()->id);
            });
        }

        return $model;
    },

]);
