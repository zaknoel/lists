<?php

namespace Zak\Lists\Fields;

use App\Models\Visit;
use Illuminate\Support\Arr;

class Select extends Text
{
    public array $enum = [];
    public array $selected = [];

    public function enum($array): static
    {
        $this->enum = $array;
        return $this;
    }

    public function componentName(): string
    {
        return "select";
    }

    public function beforeShow()
    {
        if (old($this->attribute)) {
            $this->selected = is_array(old($this->attribute)) ? old($this->attribute) : [old($this->attribute)];
        } elseif ($this->value) {
            $this->selected = is_array($this->value) ? $this->value : [$this->value];
        } elseif ($this->default) {
            $this->selected = is_array($this->default) ? $this->default : [$this->default];
        }
        ;

    }
    public function changeListValue($value){

            if(is_array($value)){
                $values=[];
                foreach ($value as $v) $values[]=$this->enum[$v]??'';
                return implode(", ", $values);
            }else{
                return $this->enum[$value]??"";
            }


    }
    public function changeDetailValue(mixed $value)
    {
        return $this->changeListValue($value); // TODO: Change the autogenerated stub
    }
    public function filteredValue()
    {
        return trim(implode(' | ', $this->filter_value)) ?: "Все ";
    }

    public function generateFilter($query)
    {
        $this->filter_value = [];

        if (request()?->has($this->attribute)) {
            $v = explode("⚬", request()?->get($this->attribute, ''));
            if ($v && ($this instanceof Relation || $this instanceof BelongToMany)) {
                $this->filter_value = $this->model::query()->whereIn('id', $v)->get(['id', 'name'])->pluck('name', 'id')->toArray();
            } elseif ($v) {
                $this->filter_value = Arr::where($this->enum, static function ($value, $key) use ($v) {
                    return in_array($key, $v, false);
                });
            }
        }
        if ($this->filter_value) {
            if ($this instanceof BelongToMany) {
                $value = array_keys($this->filter_value);
                $a=(new Visit())->products()->getRelatedPivotKeyName();
                $query->whereHas($this->attribute, function ($subQuery) use ($value, $a) {
                    return $subQuery->whereIn($a, $value);
                });
            }else{
                $query->whereIn($this->attribute, array_keys($this->filter_value));
            }
        }

    }

}