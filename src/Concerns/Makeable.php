<?php

namespace Zak\Lists\Concerns;

trait Makeable
{
    /**
     * Create a new instance of the class.
     */
    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments);
    }
}
