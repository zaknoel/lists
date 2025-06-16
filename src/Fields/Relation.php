<?php

namespace Zak\Lists\Fields;

use Zak\Lists\ListComponent;

class Relation extends Select
{
    public bool $searchable = false;

    public string $model;

    public string $field = 'name';

    public array $filter = [];

    public string $relationName = '';

    public string $list = '';

    public bool $createButton = false;

    public function list($list): static
    {
        $this->list = $list;

        return $this;
    }

    public function createButton($create = true): static
    {
        $this->createButton = $create;

        return $this;
    }

    public function searchable(): static
    {
        $this->searchable = false;

        return $this;
    }

    public function model($model): static
    {
        $this->model = $model;

        return $this;
    }

    public function relationName($name): static
    {
        $this->relationName = $name;

        return $this;
    }

    public function filter($filter = []): static
    {
        $this->filter[] = $filter;

        return $this;
    }

    public function field($field): static
    {
        $this->field = $field;

        return $this;
    }

    public function componentName(): string
    {
        return 'relation';
    }

    public function handleFill()
    {
        parent::handleFill();
        if ($this->selected) {
            $query = $this->model::whereIn('id', $this->selected);
            foreach ($this->filter as $filter) {
                if (! isset($filter[1])) {
                    $query->{$filter[0]}();
                } elseif ($filter[1] === 'in') {
                    $query->whereIn($filter[0], $filter[2]);
                } else {
                    $query->where($filter[0], $filter[1], $filter[2]);
                }
            }

            $this->enum($query->get(['id', $this->field])->pluck($this->field, 'id')->toArray());
        }
    }

    public function detailHandler()
    {
        $this->indexHandler();
    }

    public function indexHandler()
    {
        $value = $this->item->{$this->attribute};
        if ($value) {
            $attr = str_replace('_id', '', $this->attribute);
            if ($this->list && auth()->user()->can('viewAny', $this->model)) {
                $item = $this->item->{$attr};
                $c=ListComponent::getComponent($this->list);
                $this->value = "<a class='text-secondary' href='".$c->getRoute('lists_detail', $this->list, $item)."' target='_blank'>".$item->{$this->field}.'</a>';
            } else {
                $this->value = $this->item->{$attr}?->{$this->field};
            }
        }
    }
}
