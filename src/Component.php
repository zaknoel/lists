<?php

namespace Zak\Lists;
use Closure;
use Exception;
use Illuminate\Support\Arr;
use Zak\Lists\Fields\BelongToMany;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\Relation;
use Zak\Lists\Models\UserOption;

class Component
{
    public string $model = "";
    /** @var list<Field> $fields */
    public array $fields = [];
    /** @var list<Action> $actions */
    public array $actions = [];
    public bool $delete = true;
    public string $singleLabel = "";
    public string $label = "";
    public Closure|null $onModel = null;
    public Closure|null $onSearchModel = null;
    public array $pages = [];
    public UserOption $options;
    public string $grid_id = "";
    public string $customScript = "";
    private Closure|null $OnBeforeSave = null;
    private Closure|null $onAction = null;
    private Closure|null $OnAfterSave = null;
    private Closure|null $OnBeforeDelete = null;
    private Closure|null $OnAfterDelete = null;
    private Closure|null $OnList = null;
    private Closure|null $OnDetail = null;
    private Closure|bool $canAddItem = false;
    private Closure|bool $canEditItem = false;
    private Closure|bool $canDeleteItem = false;

    /**
     * @throws Exception
     */
    public function __construct($data)
    {
        //default value
        $default = [
            "actions" => array_filter([
                Action::make("Просмотр")->showAction()->default(),
                Action::make("Редактировать")->editAction(),
                Action::make("Удалить")->deleteAction(),
            ]),
        ];
        $default = array_merge($default, $data);
        Arr::map($default, fn($value, $key) => $this->$key = $value);
        if (!$this->model) {
            throw new \RuntimeException("Model not set!");
        }
        $this->grid_id = $this->model;
        $this->options = UserOption::firstOrCreate(
            [
                "user_id" => auth()->user()->id,
                "name" => $this->grid_id
            ],
            [
                "user_id" => auth()->user()->id,
                "name" => $this->grid_id,
                "value" => [
                    "columns" => [],
                    "sort" => [],
                    "filters" => [],
                    'cur_sort' => []
                ]
            ]

        );
        //$this->options->data=$this->options->value;

        //dd($this->options->data);
    }

    public static function init(array $data): static
    {
        return new static($data);
    }

    public function canEdit($item)
    {
        return $this->canEditItem && $this->canEditItem($item);
    }

    public function canEditItem($item): bool
    {
        return is_callable($this->canEditItem) ? call_user_func($this->canEditItem, $item) : $this->canEditItem;
    }

    public function canDelete($item)
    {
        return $this->canDeleteItem && $this->canDeleteItem($item);
    }

    public function canDeleteItem($item): bool
    {
        return is_callable($this->canDeleteItem) ? call_user_func($this->canDeleteItem, $item) : $this->canDeleteItem;
    }

    public function canAdd()
    {
        return $this->canAddItem && $this->canAddItem();
    }

    public function canAddItem(): bool
    {
        return is_callable($this->canAddItem) ? call_user_func($this->canAddItem) : $this->canAddItem;
    }

    public function OnBeforeSave($model)
    {
        if (is_callable($this->OnBeforeSave) && $this->OnBeforeSave) {
            return call_user_func($this->OnBeforeSave, $model);
        }
        return $model;
    }

    public function OnAfterSave($model)
    {
        if (is_callable($this->OnAfterSave) && $this->OnAfterSave) {
            return call_user_func($this->OnAfterSave, $model);
        }
        return $model;
    }

    public function OnBeforeDelete($model)
    {
        if (is_callable($this->OnBeforeDelete) && $this->OnBeforeDelete) {
            return call_user_func($this->OnBeforeSave, $model);
        }
        return $model;
    }

    public function OnAfterDelete($model)
    {
        if (is_callable($this->OnAfterDelete) && $this->OnAfterDelete) {
            return call_user_func($this->OnAfterDelete, $model);
        }
        return $model;
    }

    public function OnList($model)
    {
        if (is_callable($this->OnList) && $this->OnList) {
            return call_user_func($this->OnList, $model);
        }
        return $model;
    }

    public function OnDetail($model)
    {
        if (is_callable($this->OnDetail) && $this->OnDetail) {
            return call_user_func($this->OnDetail, $model);
        }
        return $model;
    }

    public function getSingleLabel(): string
    {
        return $this->singleLabel;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function scripts(): string
    {
        $scripts = [
            "location" => [
                '    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=f583857c-aaf5-454e-943b-d94c3e908c3f"
            type="text/javascript"></script>'
            ],
            'checkbox' => [
                '<link rel="stylesheet" href="/vendor/zak/lists/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css">',
                '<script src="/vendor/zak/lists/bootstrap-switch/dist/js/bootstrap-switch.min.js"></script>',
            ]
        ];

        $result = [];
        foreach ($scripts as $k => $v) {
            if (Arr::where($this->fields, fn(Field $item) => $item->componentName() === $k)) {
                $result[] = implode(PHP_EOL, $v);
            }
        }
        $result[] = $this->customScript;
        return implode(PHP_EOL, $result);
    }

    public function getModel()
    {
        $relations = [];
        foreach ($this->fields as $field) {
            if (
                $field->show_in_index
                && (!$this->options->value["columns"] || in_array($field->attribute, $this->options->value["columns"], false))
            ) {
                if ($field instanceof Relation) {
                    $rname = $field->relationName ?: str_replace("_id", "", $field->attribute);
                    if (method_exists($this->model, $rname)) {
                        $relations[] = $rname;
                    } else {
                        file_put_contents(app_path('related_methods.log'), PHP_EOL . $this->model . '->' . $rname, FILE_APPEND);
                    }

                } elseif ($field instanceof BelongToMany) {
                    $relations[] = $field->attribute;
                }
            }
        }
        $m = $this->model::query();
        if ($relations) $m->with($relations);
        return $this->OnSearchModel($m);
    }

    public function OnSearchModel($model)
    {

        if (is_callable($this->onSearchModel) && $this->onSearchModel) {
            return call_user_func($this->onSearchModel, $model);
        }
        return $model;
    }

    public function OnModel($model)
    {
        if (is_callable($this->onModel) && $this->onModel) {
            return call_user_func($this->onModel, $model);
        }
        return $model;
    }

    public function getActions($item)
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
