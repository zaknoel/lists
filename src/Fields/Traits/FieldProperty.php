<?php

namespace Zak\Lists\Fields\Traits;

use Illuminate\Database\Eloquent\Model;

trait FieldProperty
{
    public string $view = '';

    public bool $sortable = false;

    public bool $defaultAction = false;

    public bool $filterable = true;

    public bool $searchable = true;

    public bool $show_in_index = true;

    public bool $show_in_detail = true;

    public bool $show_on_update = true;

    public bool $show_on_add = true;

    public bool $virtual = false;

    public bool $required = false;

    public bool $multiple = false;

    public int $width = 6;

    public array $rules = [];

    public mixed $default = '';

    public string $jsOptions = '';

    public ?Model $item = null;

    public function view($view): static
    {
        $this->view = $view;

        return $this;
    }

    public function item($item)
    {
        $this->item = $item;
        $this->value = $item->{$this->attribute};

        return $this;
    }

    public function default($value = ''): static
    {
        $this->default = $value;

        return $this;
    }

    public function width($col = 12): static
    {
        $this->width = $col;

        return $this;
    }

    public function defaultAction($value=true): static
    {
        $this->defaultAction = $value;

        return $this;
    }

    public function filterable($value=true): static
    {
        $this->filterable = $value;

        return $this;
    }

    public function multiple($value=true): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function sortable($value=true): static
    {
        $this->sortable = $value;

        return $this;
    }

    public function showOnIndex($value=true): static
    {
        $this->show_in_index = $value;

        return $this;
    }

    public function virtual($value=true): static
    {
        $this->virtual = $value;

        return $this;
    }

    public function required($value=true): static
    {
        $this->required = $value;

        return $this;
    }

    public function searchable($value=true): static
    {
        $this->searchable = $value;

        return $this;
    }

    public function hideOnIndex($value=true): static
    {
        $this->show_in_index = !$value;

        return $this;
    }

    public function showOnDetail($value=true): static
    {
        $this->show_in_detail = $value;

        return $this;
    }

    public function hideOnDetail($value=true): static
    {
        $this->show_in_detail = !$value;

        return $this;
    }

    public function showOnUpdate($value=true): static
    {
        $this->show_on_update = $value;

        return $this;
    }

    public function hideOnUpdate($value=true): static
    {
        $this->show_on_update = !$value;

        return $this;
    }

    public function showOnAdd($value=true): static
    {
        $this->show_on_add = $value;

        return $this;
    }

    public function hideOnAdd($value=true): static
    {
        $this->show_on_add = !$value;

        return $this;
    }

    public function showOnForms($value=true): static
    {
        $this->show_on_add = $value;
        $this->show_on_update = $value;

        return $this;
    }

    public function hideOnForms($value=true): static
    {
        $this->show_on_add = !$value;
        $this->show_on_update = !$value;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
