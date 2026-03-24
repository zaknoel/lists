<?php

declare(strict_types=1);

/**
 * IDE-only helper definitions for Zak/Lists component files.
 *
 * This file is not used at runtime.
 */

namespace Zak\Lists\IdeHelper {
    use Closure;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\Database\Eloquent\Model;
    use Zak\Lists\Action;
    use Zak\Lists\BulkAction;
    use Zak\Lists\Component;
    use Zak\Lists\Fields\Field;

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, Field>  $fields
     * @param  array<int, Action>|null  $actions
     * @param  array<int, BulkAction>|null  $bulkActions
     */
    function component(
        string $model,
        string $label,
        string $singleLabel,
        array $fields = [],
        ?array $actions = null,
        ?array $bulkActions = null,
        ?Closure $onQuery = null,
        ?Closure $onIndexQuery = null,
        ?Closure $onDetailQuery = null,
        ?Closure $onEditQuery = null,
        ?Closure $onBeforeSave = null,
        ?Closure $onAfterSave = null,
        ?Closure $onBeforeDelete = null,
        ?Closure $onAfterDelete = null,
    ): Component {
        return new Component(
            model: $model,
            label: $label,
            singleLabel: $singleLabel,
            fields: $fields,
            actions: $actions,
            bulkActions: $bulkActions,
            OnQuery: $onQuery,
            OnIndexQuery: $onIndexQuery,
            OnDetailQuery: $onDetailQuery,
            OnEditQuery: $onEditQuery,
            OnBeforeSave: $onBeforeSave,
            OnAfterSave: $onAfterSave,
            OnBeforeDelete: $onBeforeDelete,
            OnAfterDelete: $onAfterDelete,
        );
    }

    /**
     * @param  Closure(Builder): Builder  $callback
     * @return Closure(Builder): Builder
     */
    function queryCallback(Closure $callback): Closure
    {
        return $callback;
    }
}
