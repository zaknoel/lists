<?php

namespace Zak\Lists\Fields;

class Relation extends Select
{
    public bool $searchable = false;
    public string $model;
    public string $field="name";
    public array $filter=[];
    public string $relationName='';
    public function model($model): static
    {
        $this->model=$model;
        return $this;
    }
    public function relationName($name): static
    {
        $this->relationName=$name;
        return $this;
    }
    public function filter($filter=[]): static
    {
        $this->filter[]=$filter;
        return $this;
    }

    public function field($field): static
    {
        $this->field=$field;
        return $this;
    }
    public function componentName(): string
    {
        return "relation";
    }
    public function beforeShow()
    {
        parent::beforeShow();
        if($this->selected){
            $query=$this->model::whereIn("id", $this->selected);
            foreach ($this->filter as $filter){
                if(!isset($filter[1]))
                {
                    $query->{$filter[0]}();
                }
                elseif($filter[1]==="in"){
                    $query->whereIn($filter[0], $filter[2]);
                }else{
                    $query->where($filter[0], $filter[1], $filter[2]);
                }
            }

            $this->enum($query->get(["id", $this->field])->pluck($this->field, "id")->toArray());
        }
    }
    public function changeListValue($value){
        if($value){
            return $this->item->{str_replace("_id", "", $this->attribute)}?->{$this->field};
        }
        return  $value;

    }
    public function changeDetailValue($value){
        return $this->changeListValue($value);
    }

}
