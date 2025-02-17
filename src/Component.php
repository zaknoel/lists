<?php

namespace Zak\Lists;

use Arr;
use Artisan;
use Closure;
use Exception;
use InvalidArgumentException;
use Zak\Lists\Fields\BelongToMany;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\Relation;
use Zak\Lists\Models\UserOption;

class Component
{
    public UserOption $options;

    public string $grid_id = '';

    private string $className;

    public function __construct(
        protected string $model,
        protected string $label,
        protected string $singleLabel,
        protected array $fields = [],
        protected ?array $actions = null,
        protected ?array $pages = null,
        protected string $customScript = '',

        protected ?Closure $OnQuery = null,
        protected ?Closure $OnIndexQuery = null,
        protected ?Closure $OnDetailQuery = null,
        protected ?Closure $OnEditQuery = null,

        protected ?Closure $OnBeforeSave = null,
        protected ?Closure $OnAfterSave = null,
        protected ?Closure $OnBeforeDelete = null,
        protected ?Closure $OnAfterDelete = null,
        protected ?Closure $canView = null,
        protected ?Closure $canViewAny = null,
        protected ?Closure $canAdd = null,
        protected ?Closure $canEdit = null,
        protected ?Closure $canDelete = null,
    ) {
        // init component
        $this->className = class_basename($this->model);
        $user = auth()->user();
        $this->checkPolice();
        $this->canAdd = $this->canAdd ?? fn () => $user->can('add', $this->model);
        $this->canEdit = $this->canEdit ?? static fn ($item) => $user->can('edit', $item);
        $this->canDelete = $this->canDelete ?? static fn ($item) => $user->can('delete', $item);
        $this->canView = $this->canView ?? static fn ($item) => $user->can('view', $item);
        $this->canViewAny = $this->canViewAny ?? fn () => $user->can('viewAny', $this->model);

        if (! $this->userCanViewAny()) {
            abort(403);
        }
        if (is_null($this->actions)) {
            $this->actions = array_filter([
                Action::make('Просмотр')->showAction()->default(),
                Action::make('Редактировать')->editAction(),
                Action::make('Удалить')->deleteAction(),
            ]);
        }
        if (! $this->model) {
            throw new InvalidArgumentException('Model not set!');
        }
        $this->grid_id = $this->model;
        $this->options = UserOption::firstOrCreate(
            [
                'user_id' => auth()->user()->id,
                'name' => $this->grid_id,
            ],
            [
                'user_id' => auth()->user()->id,
                'name' => $this->grid_id,
                'value' => [
                    'columns' => [],
                    'sort' => [],
                    'filters' => [],
                    'cur_sort' => [],
                ],
            ]

        );

    }

    private function checkPolice(): void
    {
        $path = app_path('Policies/'.$this->className.'Policy.php');
        if (! file_exists($path)) {
            Artisan::call('make:policy', ['name' => $this->className.'Policy', '-m' => $this->model]);
            $policyFilePath = app_path('Policies/'.$this->className.'Policy.php');
            $policyContent = file_get_contents($policyFilePath);
            // replace all false to true
            $policyContent = str_replace('return false;', 'return true;', $policyContent);
            file_put_contents($policyFilePath, $policyContent);
        }
    }

    public function userCanViewAny()
    {
        return $this->canViewAny && is_callable($this->canViewAny) ? call_user_func($this->canViewAny) : $this->canViewAny;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSingleLabel(): string
    {
        return str($this->singleLabel)->lower();
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function getCustomScript(): string
    {
        return $this->customScript;
    }

    public function eventOnIndexQuery($query): mixed
    {
        $this->eventOnQuery($query);
        $relations = [];
        foreach ($this->getFields() as $field) {
            if (
                $field->show_in_index
                && (! $this->options->value['columns'] || in_array($field->attribute, $this->options->value['columns'],
                    false))
            ) {
                if ($field instanceof Relation) {
                    $rname = $field->relationName ?: str_replace('_id', '', $field->attribute);
                    if (method_exists($this->model, $rname)) {
                        $relations[] = $rname;
                    } else {
                        report(new Exception('Relation not found: '.$this->model.'->'.$rname));
                    }
                } elseif ($field instanceof BelongToMany) {
                    $relations[] = $field->attribute;
                }
            }
        }
        if ($relations) {
            $query->with($relations);
        }

        if ($this->OnIndexQuery && is_callable($this->OnIndexQuery)) {
            return call_user_func($this->OnIndexQuery, $query);
        }

        return $query;
    }

    public function eventOnQuery($query): mixed
    {
        if ($this->OnQuery && is_callable($this->OnQuery)) {
            return call_user_func($this->OnQuery, $query);
        }

        return $query;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function eventOnDetailQuery($query): mixed
    {
        $this->eventOnQuery($query);
        if ($this->OnDetailQuery && is_callable($this->OnDetailQuery)) {
            return call_user_func($this->OnDetailQuery, $query);
        }

        return $query;
    }

    public function eventOnEditQuery($query): mixed
    {
        $this->eventOnQuery($query);
        if ($this->OnEditQuery && is_callable($this->OnEditQuery)) {
            return call_user_func($this->OnEditQuery, $query);
        }

        return $query;
    }

    public function eventOnBeforeSave($item): mixed
    {
        if ($this->OnBeforeSave && is_callable($this->OnBeforeSave)) {
            return call_user_func($this->OnBeforeSave, $item);
        }

        return $item;
    }

    public function eventOnAfterSave($item): mixed
    {
        if ($this->OnAfterSave && is_callable($this->OnAfterSave)) {
            return call_user_func($this->OnAfterSave, $item);
        }

        return $item;
    }

    public function eventOnBeforeDelete($item): mixed
    {
        if ($this->OnBeforeDelete && is_callable($this->OnBeforeDelete)) {
            return call_user_func($this->OnBeforeDelete, $item);
        }

        return $item;
    }

    public function eventOnAfterDelete($item): mixed
    {
        if ($this->OnAfterDelete && is_callable($this->OnAfterDelete)) {
            return call_user_func($this->OnAfterDelete, $item);
        }

        return $item;
    }

    public function userCanView($item)
    {
        return $this->canView && is_callable($this->canView) ? call_user_func($this->canView,
            $item) : $this->canView;
    }

    public function userCanAdd()
    {
        return $this->canAdd && is_callable($this->canAdd) ? call_user_func($this->canAdd) : $this->canAdd;
    }

    public function userCanEdit($item)
    {
        return $this->canEdit && is_callable($this->canEdit) ? call_user_func($this->canEdit,
            $item) : $this->canEdit;
    }

    public function userCanDelete($item)
    {
        return $this->canDelete && is_callable($this->canDelete) ? call_user_func($this->canDelete,
            $item) : $this->canDelete;
    }

    public function scripts(): string
    {
        $scripts = [
            'location' => [
                '    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=f583857c-aaf5-454e-943b-d94c3e908c3f"
            type="text/javascript"></script>',
            ],
            'checkbox' => [
                '<link rel="stylesheet" href="/vendor/lists/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css">',
                '<script src="/vendor/lists/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>',
            ],
        ];

        $result = [];
        foreach ($scripts as $k => $v) {
            if (Arr::where($this->fields, fn (Field $item) => $item->componentName() === $k)) {
                $result[] = implode(PHP_EOL, $v);
            }
        }
        $result[] = $this->customScript;

        return implode(PHP_EOL, $result);
    }

    public function getFilteredFields(Closure $callback): array
    {
        return array_filter($this->fields, $callback);
    }

    public function setFields($fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function getQuery()
    {
        return $this->model::query();
    }

    public function getFilteredActions($item)
    {
        $actions = [];
        foreach ($this->actions as $action) {
            if ($action->isShown($this, $item)) {
                $actions[] = $action;
            }
        }

        return $actions;

    }
}
