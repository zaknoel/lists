<?php

namespace Zak\Lists\Fields;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class File extends Text
{
    public string $disk="public";
    public string $path="files";
    public bool $searchable = false;
    public bool $filterable=false;
    public array $rules=[
        'file'=>'Неправильный файл',
        'max:60048'=>'The file size must not exceed 60MB.'
    ];
    public function disk($disk): static
    {
        $this->disk=$disk;
        return $this;
    }
    public function path($path): static
    {
        $this->path=$path;
        return $this;
    }
    public function componentName(): string
    {
        return "file";
    }
    public function getRules($item = null):array
    {
        $result=[];
        if($this->multiple){
            $result[$this->attribute][]="array";
            if(!$this->required){
                $result[$this->attribute.".*"][]="nullable";
            }else{
                $result[$this->attribute.".*"][]="required";
            }
            foreach ($this->rules as $rule=>$message){
                $result[$this->attribute.".*"][]=$rule;
            }
        }else{
            if(!$this->required){
                $result[$this->attribute][]="nullable";
            }else{
                $result[$this->attribute][]="required";
            }
            foreach ($this->rules as $rule=>$message){
                $result[$this->attribute][]=$rule;
            }
        }


        return  $result;
    }

    public function getRuleParams():array
    {
        $result=[];
        if($this->multiple){
            $result[$this->attribute.".array"]="Must be array";
            if($this->required){
                $result[$this->attribute.".*.required"]="Fields ".$this->showLabel().' must be filled!';
            }
            foreach ($this->rules as $rule=>$message){
                $result[$this->attribute.".*.".$rule]=$message;
            }
        }else{
            if($this->required){
                $result[$this->attribute.".required"]="Fields ".$this->showLabel().' must be filled!';
            }
            foreach ($this->rules as $rule=>$message){
                $result[$this->attribute.".".$rule]=$message;
            }
        }


        return  $result;
    }
    public function beforeSave(mixed $attribute)
    {
        $delete=request()?->get('delete')??[];
        if(array_key_exists($this->attribute, $delete) && $this->value){
            Storage::disk("public")->delete(str_replace("//","/", $this->value));
        }elseif($attribute){
            Storage::disk("public")->delete(str_replace("//","/", $this->value));
        }
        if($attribute){
            $image = $attribute;
            $filename = time() . '_' . $image->getClientOriginalName();
            $attribute = $image->storeAs($this->path, $filename, ["disk" => $this->disk]);

            if($this->beforeSaveCallback && is_callable($this->beforeSaveCallback)){
                $attribute=call_user_func_array($this->beforeSaveCallback, $attribute);
            }
        }

        return $attribute;
    }
    public function changeListValue($value){
        if($value){
            return "<a target='_blank' download='' href='".Storage::url($value)."'>Скачать файл</a>";
        }
        return  $value;

    }
    public function changeDetailValue($value){
        return $this->changeListValue($value);
    }

}
