<?php

namespace Zak\Lists\Fields;

class ID extends Text
{
    public string $jsOptions = "width:'50px'";

    public function componentName(): string
    {
        return 'text';
    }

    public function type()
    {
        return 'id';
    }

    public function handleFill()
    {
        $this->value = $this->item->{$this->attribute};
    }

    public function saveHandler($item, $data)
    {
        return $item;
    }

    public function detailHandler()
    {
        $this->value = $this->item->{$this->attribute};
    }

    public function indexHandler()
    {
        $this->value = $this->item->{$this->attribute};
    }
}
