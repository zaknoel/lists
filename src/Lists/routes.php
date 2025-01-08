<?php

use App\Models\Company;
use App\Models\District;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\Question;
use App\Zak\Component\Component;
use App\Zak\Component\Fields\BelongToMany;
use App\Zak\Component\Fields\Boolean;
use App\Zak\Component\Fields\ID;
use App\Zak\Component\Fields\Number;
use App\Zak\Component\Fields\Text;

return Component::init([
    "model" => \App\Models\Route::class,
    "singleLabel" => "маршрут",
    "label" => "Маршруты",
    "fields" => [
        ID::make("ID", 'id')->hideOnForms()->sortable()->filterable()->showOnIndex(),
        Boolean::make("Активность", 'active')->sortable()->filterable()->default(true),
        Text::make("Название", "name")->sortable()->defaultAction(),
        Number::make("Сортировка", "sort")->sortable()->default(500),
        BelongToMany::make("Районы", 'districts')
            ->required()
            ->model(District::class)
            ->field("name")
            ->filter(["active", "=", true])
        ,
    ],
    'pages' => [
        "questions" => [
            'title' => 'Карта',
            'view' => static function (\App\Models\Route $item) {

                $districts = $item->districts->pluck('id');
                $cData = [];
                $stat = [
                    'store' => 0,
                    'doctor' => 0,
                    'number' => 0
                ];
                if ($districts) {
                    $companies = Company::whereActive(true)
                        ->whereIn('district_id', $districts)
                        ->whereNotNull('location')
                        ->get(['id', 'name', 'location']);
                    $doctors = Doctor::whereActive(true)
                        ->whereIn('district_id', $districts)
                        ->whereNotNull('location')
                        ->get(['id', 'name', 'location']);
                    $locs = [];
                    foreach ($companies as $v) {
                        if (isset($locs[$v->location])) {
                            $v->location = changeLocation($v->location);
                        }
                        $cData["c_".$v->id] = [
                            "id" => $v->id,
                            "location" => $v->location,
                            "name" => $v->name,
                            "type" => "store",
                        ];
                        $locs[$v->location] = 1;
                        $stat['store']++;
                    }
                    foreach ($doctors as $k => $v) {
                        if (isset($locs[$v->location])) {
                            $v->location = changeLocation($v->location);
                        }
                        $cData["d_".$v->id] = [
                            "id" => $v->id,
                            "location" => $v->location,
                            "name" => $v->name,
                            "type" => "doctor",
                        ];
                        $locs[$v->location] = 1;
                        $stat['doctor']++;
                    }
                }
                $data = $item->data ?? [];
                foreach ($data as $k => $v) {
                    $key = $v['key'];
                    if ($cData[$key]) {
                        $stat['number']++;
                        $cData[$key]['number'] = $v['number'];
                    }
                }
                $first = array_key_first($cData);
                $startLoc = explode(",", $cData[$first]['location']);


                return view('pages.route_map',
                    compact('item', 'stat', 'cData', 'startLoc', 'data')
                );
            }
        ]
    ]

]);
