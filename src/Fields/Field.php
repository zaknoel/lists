<?php

namespace Zak\Lists\Fields;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Nova\Makeable;

abstract class Field
{
    use Makeable;

    public string $name;
    public string $type;
    public string $component_name;

    public string $attribute;

    public $value;
    public $filter_value = null;

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

    public Closure|null $listDisplayCallback = null;
    public Closure|null $detailDisplayCallback = null;
    public Closure|null $beforeSaveCallback = null;
    public Closure|null $beforeFillCallback = null;
    public array $rules = [];
    public mixed $default = "";
    public string $jsOptions = "";

    public Model|null $item = null;

    public function __construct($name, $attribute = null)
    {
        $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        $this->name = $name;
    }

    public function addRule($rule, $message): static
    {
        $this->rules[$rule] = $message;
        return $this;
    }

    public function item($item)
    {
        $this->item = $item;
        return $this;
    }

    public function default($value = ""): static
    {
        $this->default = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function show()
    {
        $view = "lists::fields.".$this->componentName();
        if (!view()->exists($view)) {
            //create view
            $file = resource_path("views/vendor/lists/fields/".$this->componentName().".blade.php");
            if (!file_exists($file)) {
                file_put_contents($file, "<div></div>");
            }
        }
        $this->beforeShow();
        return view($view, ["field" => $this]);
    }

    abstract public function componentName();

    public function beforeShow()
    {
    }

    public function displayInList(Closure $closure): static
    {
        $this->listDisplayCallback = $closure;
        return $this;
    }

    public function displayInDetail(Closure $closure): static
    {
        $this->detailDisplayCallback = $closure;
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

    public function getRules($item = null): array
    {
        $result = [];
        if ($this->multiple) {
            $result[] = "array";
        }
        if (!$this->required) {
            $result[] = "nullable";
        } else {
            $result[] = "required";
        }
        foreach ($this->rules as $rule => $message) {
            if ($item && str_contains($rule, "unique:")) {
                $result[] = Rule::unique(explode(":", $rule)[1] ?? "")->ignore($item->id);
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
            $result[$this->attribute.".array"] = "Must be array";
        }
        if ($this->required) {
            $result[$this->attribute.".required"] = "Fields ".$this->showLabel().' must be filled!';
        }
        foreach ($this->rules as $rule => $message) {
            if (str_contains($rule, ":")) {
                $rule = explode(":", $rule)[0] ?? "";
            }
            $result[$this->attribute.".".$rule] = $message;
        }
        return $result;
    }

    public function showLabel(): string
    {
        return $this->name;
    }

    public function beforeSave(mixed $attribute)
    {
        if ($this->beforeSaveCallback && is_callable($this->beforeSaveCallback)) {
            $attribute = call_user_func_array($this->beforeSaveCallback, $attribute);
        }
        return $attribute;
    }

    public function fillValue($value): void
    {

        if ($this->beforeFillCallback && is_callable($this->beforeFillCallback)) {
            $value = call_user_func_array($this->beforeSaveCallback, $value);
        }
        $this->value = $this->changeValue($value);
    }

    public function changeValue(mixed $value)
    {
        return $value;
    }

    public function showDetail($item)
    {
        $value = "";
        if (array_key_exists($this->attribute, $item->getAttributes())) {
            $value = $item->{$this->attribute};
        }
        if ($this->detailDisplayCallback && is_callable($this->detailDisplayCallback)) {
            return call_user_func($this->detailDisplayCallback, $item);
        }
        return $this->changeDetailValue($value);
    }

    public function changeDetailValue(mixed $value)
    {
        return $value;
    }

    public function showIndex($item, $list, $action = null)
    {
        $value = "";
        if (array_key_exists($this->attribute, $item->getAttributes())) {
            $value = $item->{$this->attribute};
        }
        if ($this->listDisplayCallback && is_callable($this->listDisplayCallback)) {
            return call_user_func($this->listDisplayCallback, $item);
        }
        if ($action && $this->defaultAction) {
            return $action->getLink($item, $list, $this->changeListValue($value));
        }
        return $this->changeListValue($value);
    }

    public function changeListValue(mixed $value)
    {
        return $value;
    }

    public function saveValue($item, $value)
    {
        return $item->{$this->attribute} = $value;
    }

    public function arrayValue($array)
    {
        return implode("|", $array);
    }

    public function showFilter()
    {
        return view('lists::filter.main', ['field' => $this]);
    }

    public function filteredValue()
    {
        return "все";
    }

    public function filterContent()
    {

        $view = "lists::filter.".$this->componentName();

        if (!view()->exists($view)) {
            //create view
            $file = resource_path("views/vendor/lists/filter/".$this->componentName().".blade.php");
            if (!file_exists($file)) {
                file_put_contents($file, "<div></div>");
            }
        }
        $this->beforeFilter();
        return view($view, ["field" => $this]);
    }

    public function beforeFilter()
    {

    }

    public function generateFilter($query)
    {
        return $query;
    }

}
