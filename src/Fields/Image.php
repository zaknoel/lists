<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Facades\Storage;

class Image extends File
{
    public int $max_width = 100;

    public bool $filterable = false;

    public int $max_height = 100;

    public array $rules = [
        'image' => 'Неправильный файл',
        'max:60048' => 'The image size must not exceed 60MB.',
    ];

    public function maxWidth($width): static
    {
        $this->max_width = $width;

        return $this;
    }

    public function maxHeight($height): static
    {
        $this->max_height = $height;

        return $this;
    }

    public function componentName(): string
    {
        return 'image';
    }

    public function indexHandler()
    {
        if ($this->item->{$this->attribute}) {
            $this->value = "<a target='_blank' download='' href='".Storage::url($this->item->{$this->attribute})."'>
                <img src='".Storage::url($this->item->{$this->attribute})."' style='max-width: 100px; max-height: 100px;'>
</a>";
        } else {
            $this->value = '';
        }

    }

    public function detailHandler()
    {
        $this->indexHandler();
    }
}
