<?php

use App\Models\Test;
use App\Models\User;
use App\Zak\Component\Action;
use App\Zak\Component\Component;
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
    "model" => Test::class,
    "singleLabel" => "тест",
    "label" => "Тесты",
    "actions" => [
        Action::make("Просмотр")->showAction()->default(),
        Action::make("Редактировать")->editAction(),
        Action::make("Удалить")->deleteAction(),
    ],
    "fields" => [
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable(),
        Boolean::make("Is main", "is_main")->default(0)->width(12)->sortable()->filterable(),
        Text::make('Name', "name")->required()->width(6)->sortable()->filterable()->defaultAction(),
        Select::make('Select', "select")->width(6)
            ->enum(["a" => "B", "c" => "D", "G" => "F"])
            ->default()->sortable()->filterable()
        ,
        Relation::make('User', "user_id")
            ->width(6)
            ->model(User::class)
            ->filter(["id", "<", 10])
            ->filter(["name", "!=", ""])
            ->field('name')
            ->default(""),
        Number::make("Number", "number")->width(6)->width(6)->sortable()->filterable(),
        Location::make("Location", "location"),
        Image::make("Image", "image")->width(6),
        File::make("File", "file")->width(6),
        Email::make("Email", "email")->width(6),
        Date::make("Date", "date")->width(6)->sortable()->filterable(),

        Date::make("Updated at", "updated_at")->width(6)->withTime()->sortable()->filterable(),
        Date::make("Created at", "created_at")->width(6)->withTime()->sortable()->filterable(),

    ],

]);
