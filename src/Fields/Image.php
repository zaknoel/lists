<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Facades\Storage;

class Image extends File
{
    public bool $searchable = false;
    public int $max_width=100;
    public bool $filterable=false;
    public int $max_height=100;
    public array $rules=[
        'image'=>'Неправильный файл',
        'max:60048'=>'The image size must not exceed 60MB.'
    ];
    public function maxWidth($width):static
    {
        $this->max_width=$width;
        return $this;
    }
    public function maxHeight($height):static
    {
        $this->max_height=$height;
        return $this;
    }

    public function componentName(): string
    {
        return "image";
    }
    public function changeListValue($value){
        if($value){
            return "<a class='d-block' target='_blank' download='' href='".Storage::url($value)."'><img style='max-width:".$this->max_width."px; max-height:".$this->max_height."px'  src='".Storage::url($value)."'></a>";
        }
        return  $value;

    }
    public function changeDetailValue($value){
        return $this->changeListValue($value);
    }

}
