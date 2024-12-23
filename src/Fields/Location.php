<?php

namespace Zak\Lists\Fields;

class Location extends Text
{
    public bool $searchable = false;
    public mixed $default='41.31620950467525, 69.27942399984737';
    public bool $filterable=false;

    public function componentName(): string
    {
        return "location";
    }
    public function changeListValue($value){
        if($value){
            $l = explode(",", $value);
            $lat = trim($l[0])??'';
            $lon = trim($l[1])??'';
            if($lat && $lon){
                $url = 'https://yandex.uz/maps/?ll=' . $lon . '%2C' . $lat . '&mode=whatshere&whatshere%5Bpoint%5D=' . $lon . '%2C' . $lat . '&whatshere%5Bzoom%5D=19&z=16';
                return "<a target='_blank'  href='".$url."'>Показать на карте</a>";
            }
            return $value;

        }
        return  $value;

    }
    public function changeDetailValue($value){
        return $this->changeListValue($value);
    }
}