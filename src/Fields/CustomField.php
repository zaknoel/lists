<?php

namespace Zak\Lists\Fields;

class CustomField extends Field
{
    public bool $searchable = false;

    public string $filtered_value = '';

    public function componentName(): string
    {
        return 'custom';
    }

    public function type(): string
    {
        return 'custom';
    }

    public function filteredValue(): string
    {
        return $this->filtered_value;
    }

    public function handleFill(): void
    {
        // TODO: Implement handleFill() method.
    }

    public function saveHandler($item, $data): void
    {
        // TODO: Implement saveHandler() method.
    }

    public function detailHandler(): void
    {
        // TODO: Implement detailHandler() method.
    }

    public function indexHandler(): void
    {
        // TODO: Implement indexHandler() method.
    }

    public function generateFilter(mixed $query = false): mixed
    {
        if (is_callable($this->filterCallback)) {
            call_user_func($this->filterCallback, $query, $this);

            return $query;
        }

        return $query;
    }
}
