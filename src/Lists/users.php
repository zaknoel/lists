<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Dist;
use App\Models\District;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\Region;
use App\Models\Script;
use App\Models\Spec;
use App\Models\Status;
use App\Models\User;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\Email;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Location;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Relation;
use App\Zak\Component\Fields\Text;

return Component::init([
    "model" => User::class,
    "singleLabel" => "мед.пред",
    "label" => "Медпредставители",
    "fields" => [

        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Text::make("Ф.И.О", "name")->sortable()->defaultAction(),
        Email::make("Email", "email")->sortable()->required()
            ->addRule('unique:users', "Мед. Пред. с таким email уже существует!")
        ,
        \App\Zak\Component\Fields\Password::make('Пароль', 'password')->hideOnAdd(),
        \App\Zak\Component\Fields\Password::make('Пароль', 'password')->required()->hideOnUpdate(),
        Text::make('Должность', 'position'),
        Text::make("Номер телефона", "phone"),
        Number::make("План визитов в аптеки", "plan"),
        Number::make("План визитов для врачей", "plan_doc"),
        Relation::make('Регион', "region_id")
            ->model(Region::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        BelongToMany::make('Район', "districts")
            ->model(District::class)
            ->filter(["active", "=", true])
            ->field('name')
        ,
        Relation::make('Маршрут', "route_id")
            ->model(\App\Models\Route::class)
            ->filter(["active", "=", true])
            ->field('name'),
        Relation::make('Категория', "category_id")
            ->model(Category::class)
            ->filter(["active", "=", true])
            ->field('name')


    ],
    "OnList" => function ($model) {
        return $model->operator();
    },
    "OnBeforeSave" => function ($model) {
        if (!$model->password) {
            $model->password = Hash::make($model->password);
        }
        $model->assignRole("operator");
    }

]);
