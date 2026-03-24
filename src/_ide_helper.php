<?php

declare(strict_types=1);

/**
 * IDE helper for app list configuration files.
 *
 * Keep this file in app/Lists so PhpStorm can infer callback signatures used in
 * `new Component(...)` definitions.
 */

namespace App\Lists\IdeHelper {
    use Closure;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Zak\Lists\Component;
    use Zak\Lists\Fields\Field;

    /** @param Closure(Builder): Builder $callback */
    function onQuery(Closure $callback): Closure
    {
        return $callback;
    }

    /** @param Closure(Model): bool $callback */
    function canView(Closure $callback): Closure
    {
        return $callback;
    }

    /** @param Closure(Model): bool $callback */
    function canEdit(Closure $callback): Closure
    {
        return $callback;
    }

    /** @param Closure(Model): bool $callback */
    function canDelete(Closure $callback): Closure
    {
        return $callback;
    }

    /** @param array<int, Field> $fields */
    function component(string $modelClass, string $label, string $singleLabel, array $fields): Component
    {
        return new Component(
            model: $modelClass,
            label: $label,
            singleLabel: $singleLabel,
            fields: $fields,
        );
    }
}

