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

    public function defaultAction(): static
    {
        $this->defaultAction = true;

        return $this;
    }

    public function filterable(): static
    {
        $this->filterable = true;

        return $this;
    }

    public function multiple(): static
    {
        $this->multiple = true;

        return $this;
    }

    public function sortable(): static
    {
        $this->sortable = true;

        return $this;
    }

    public function showOnIndex(): static
    {
        $this->show_in_index = true;

        return $this;
    }

    public function virtual(): static
    {
        $this->virtual = true;

        return $this;
    }

    public function required(): static
    {
        $this->required = true;

        return $this;
    }

    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    public function hideOnIndex(): static
    {
        $this->show_in_index = false;

        return $this;
    }

    public function showOnDetail(): static
    {
        $this->show_in_detail = true;

        return $this;
    }

    public function hideOnDetail(): static
    {
        $this->show_in_detail = false;

        return $this;
    }

    public function showOnUpdate(): static
    {
        $this->show_on_update = true;

        return $this;
    }

    public function hideOnUpdate(): static
    {
        $this->show_on_update = false;

        return $this;
    }

    public function showOnAdd(): static
    {
        $this->show_on_add = true;

        return $this;
    }

    public function hideOnAdd(): static
    {
        $this->show_on_add = false;

        return $this;
    }

    public function showOnForms(): static
    {
        $this->show_on_add = true;
        $this->show_on_update = true;

        return $this;
    }

    public function hideOnForms(): static
    {
        $this->show_on_add = false;
        $this->show_on_update = false;

        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
