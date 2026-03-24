<?php

namespace Zak\Lists\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Zak\Lists\Component;
use Zak\Lists\Contracts\QueryContract;
use Zak\Lists\Fields\BelongToMany;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\Relation;

/**
 * Строит Eloquent-запросы с нужными eager-load, колбэками компонента и фильтрами.
 */
class QueryService implements QueryContract
{
    public function buildIndexQuery(Component $component, Request $request): Builder
    {
        $query = $component->getQuery();
        $component->eventOnIndexQuery($query);
        $this->applyEagerRelations($query, $component, $component->fieldCollection()->visibleForIndex()->all());

        return $query;
    }

    public function buildDetailQuery(Component $component): Builder
    {
        $query = $component->getQuery();
        $component->eventOnDetailQuery($query);
        $this->applyEagerRelations($query, $component, $component->fieldCollection()->visibleForDetail()->all());

        return $query;
    }

    public function buildEditQuery(Component $component): Builder
    {
        $query = $component->getQuery();
        $component->eventOnEditQuery($query);
        $this->applyEagerRelations($query, $component, $component->fieldCollection()->visibleForUpdate()->all());

        return $query;
    }

    public function findOrAbort(Component $component, Builder $query, int $id): Model
    {
        $item = $query->where('id', $id)->first();

        if ($item) {
            return $item;
        }

        // Если элемент не найден — возможно, он существует вне текущего global scope
        // (например, переключился проект). Пробуем без scope и редиректим с предупреждением.
        /** @var class-string<Model> $modelClass */
        $modelClass = $query->getModel()::class;
        $itemWithoutScope = $modelClass::query()->withoutGlobalScopes()->where('id', $id)->first();

        if ($itemWithoutScope) {
            session()->flash('warning', __('lists.errors.scope_switched'));
            redirect('/')->send();
            exit;
        }

        abort(404);
    }

    /**
     * Определяет связи для eager loading из видимых полей индекса.
     *
     * @param  array<int, Field>  $fields
     * @return array<int, string>
     */
    public function resolveEagerRelations(Component $component, array $fields): array
    {
        $relations = [];
        $visibleColumns = $component->options->value['columns'] ?? [];

        foreach ($fields as $field) {
            if (! $field->show_in_index) {
                continue;
            }

            if ($visibleColumns && ! in_array($field->attribute, $visibleColumns, false)) {
                continue;
            }

            if ($field instanceof Relation) {
                $relationName = $field->relationName ?: str_replace('_id', '', $field->attribute);
                if (method_exists($component->getModel(), $relationName)) {
                    $relations[] = $relationName;
                }
            } elseif ($field instanceof BelongToMany) {
                $relations[] = $field->attribute;
            }
        }

        return array_values(array_unique($relations));
    }

    /**
     * Применяет eager loading к запросу, если есть подходящие relation fields.
     *
     * @param  array<int, Field>  $fields
     */
    private function applyEagerRelations(Builder $query, Component $component, array $fields): void
    {
        $relations = $this->resolveEagerRelations($component, $fields);

        if ($relations !== []) {
            $query->with($relations);
        }
    }
}
