<?php

namespace Zak\Lists\Fields;

class CustomField extends Field
{

    public string $filtered_value = "";
    public function componentName(): string
    {
        return "custom";
    }


    public function type()
    {
        return "custom";
    }
    public function filteredValue()
    {
        return $this->filtered_value;
    }
    public function handleFill()
    {
        // TODO: Implement handleFill() method.
    }

    public function saveHandler($item, $data)
    {
        // TODO: Implement saveHandler() method.
    }

    function detailHandler()
    {
        // TODO: Implement detailHandler() method.
    }

    public function indexHandler()
    {
        // TODO: Implement indexHandler() method.
    }

    public function generateFilter($query = false)
    {
        $this->eventBeforeFilter($query);
    }


}
