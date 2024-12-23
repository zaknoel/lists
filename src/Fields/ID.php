<?php

namespace Zak\Lists\Fields;

class ID extends Text
{
    public string $jsOptions="width:'50px'";
    public bool $show_in_index=false;
    public string $type='id';
    public function componentName(): string
    {
        return "text";
    }

}
