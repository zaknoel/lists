<?php

namespace Zak\Lists\Fields;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Zak\Lists\Concerns\Makeable;
use Zak\Lists\Fields\Casts\FieldCast;
use Zak\Lists\Fields\Contracts\Displayable;
use Zak\Lists\Fields\Contracts\Filterable;
use Zak\Lists\Fields\Contracts\Validatable;
use Zak\Lists\Fields\Traits\FieldEvents;
use Zak\Lists\Fields\Traits\FieldFilter;
use Zak\Lists\Fields\Traits\FieldProperty;

abstract class Field implements Displayable, Filterable, Validatable
{
    use FieldEvents, FieldFilter, FieldProperty;
    use Makeable;

    /** @var FieldCast|null Cast для преобразования значений этого поля */
    protected ?FieldCast $cast = null;

    public string $name;

    public bool $hide_on_export = false;

    public string $attribute;

    public $value;

    protected string $type = '';

    protected string $component_name;

    public function __construct($name, $attribute = null)
    {
        $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        $this->name = $name;
    }

    public static function create(
        $name,
        $attribute = null,
        $required = false,
        $sortable = false,
        $filterable = false,
        $searchable = false,
        $multiple = false,
        $virtual = false,
        $show_in_index = true,
        $show_in_detail = true,
        $show_on_update = true,
        $show_on_add = true,
        $width = 6,
        $default = null,
        $view = null,
        $filter_view = null,
        $defaultAction = false,
        $rules = [],
        $onBeforeFilter = null,
        $onSaveForm = null,
        $onFillForm = null,
        $onShowDetail = null,
        $onShowList = null,
        $hideOnExport = false,
    ): static {
        $instance = new static($name, $attribute);
        if ($required) {
            $instance->required();
        }
        if ($sortable) {
            $instance->sortable();
        }
        if ($filterable) {
            $instance->filterable();
        }
        if ($searchable) {
            $instance->searchable();
        }
        if ($multiple) {
            $instance->multiple();
        }
        if ($virtual) {
            $instance->virtual();
        }
        if ($show_in_index) {
            $instance->showOnIndex();
        }
        if ($show_in_detail) {
            $instance->showOnDetail();
        }
        if ($show_on_update) {
            $instance->showOnUpdate();
        }
        if ($show_on_add) {
            $instance->showOnAdd();
        }
        if ($width) {
            $instance->width($width);
        }
        if ($default) {
            $instance->default($default);
        }
        if ($view) {
            $instance->view($view);
        }
        if ($filter_view) {
            $instance->filterView($filter_view);
        }
        if ($defaultAction) {
            $instance->defaultAction();
        }
        if ($rules) {
            foreach ($rules as $rule => $message) {
                $instance->addRule($rule, $message);
            }
        }
        if ($onBeforeFilter) {
            $instance->onBeforeFilter($onBeforeFilter);
        }
        if ($onSaveForm) {
            $instance->onSaveForm($onSaveForm);
        }
        if ($onFillForm) {
            $instance->onFillForm($onFillForm);
        }
        if ($onShowDetail) {
            $instance->onShowDetail($onShowDetail);
        }
        if ($onShowList) {
            $instance->onShowList($onShowList);
        }
        if ($hideOnExport) {
            $instance->hideOnExport();
        }

        return $instance;
    }

    /**
     * Задаёт cast для трансформации значений поля при чтении и записи.
     */
    public function withCast(FieldCast $cast): static
    {
        $this->cast = $cast;

        return $this;
    }

    /**
     * Возвращает текущий cast или null.
     */
    public function getCast(): ?FieldCast
    {
        return $this->cast;
    }

    public function addRule($rule, $message): static
    {
        $this->rules[$rule] = $message;

        return $this;
    }

    public function getType(): string
    {
        return $this->type();
    }

    abstract public function type();

    public function getComponentName(): string
    {
        return $this->component_name;
    }

    public function showEdit(): void
    {
        $this->handleFill();
        $this->eventOnFillForm();
    }

    abstract public function handleFill();

    public function show(): mixed
    {
        if ($this->view) {
            return view($this->view, ['field' => $this]);
        }
        $view = 'lists::fields.'.$this->componentName();
        if (! view()->exists($view)) {
            // create view
            $file = resource_path('views/vendor/lists/fields/'.$this->componentName().'.blade.php');
            if (! file_exists($file)) {
                file_put_contents($file, '<div></div>');
            }
        }

        return view($view, ['field' => $this]);
    }

    abstract public function componentName();

    public function getRules($item = null): array
    {
        $result = [];
        if ($this->multiple) {
            $result[] = 'array';
        }
        if (! $this->required) {
            $result[] = 'nullable';
        } else {
            $result[] = 'required';
        }
        foreach ($this->rules as $rule => $message) {
            if ($item && str_contains($rule, 'unique:')) {
                $result[] = Rule::unique(explode(':', $rule)[1] ?? '')->ignore($item->id);
            } else {
                $result[] = $rule;
            }

        }

        return [$this->attribute => $result];
    }

    public function getRuleParams(): array
    {
        $result = [];
        if ($this->multiple) {
            $result[$this->attribute.'.array'] = 'Must be array';
        }
        if ($this->required) {
            $result[$this->attribute.'.required'] = 'Поля '.$this->showLabel().' обязательно для заполнения';
        }
        foreach ($this->rules as $rule => $message) {
            if (str_contains($rule, ':')) {
                $rule = explode(':', $rule)[0] ?? '';
            }
            $result[$this->attribute.'.'.$rule] = $message;
        }

        return $result;
    }

    public function showLabel(): string
    {
        return $this->name;
    }

    public function saveValue($item, $data): void
    {
        $this->saveHandler($item, $data);
        $this->eventOnSaveForm($item, $data);
    }

    abstract public function saveHandler($item, $data);

    public function showDetail(): mixed
    {
        $this->detailHandler();
        $this->eventOnShowDetail();

        return $this->value;
    }

    abstract public function detailHandler(): void;

    public function showIndex(mixed $item, string $list, mixed $action = null): mixed
    {
        $this->indexHandler();
        $this->eventOnShowList();

        if ($action && $this->defaultAction) {
            return $action->getLink($item, $list, $this->value);
        }

        return $this->value;
    }

    abstract public function indexHandler(): void;

    public function hideOnExport(): static
    {
        $this->hide_on_export = true;

        return $this;
    }
}
