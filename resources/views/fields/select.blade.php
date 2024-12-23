@php use Zak\Lists\Fields\Select; @endphp
@php /**@var Select $field*/ @endphp
<select
    @class(["form-control simple_select", 'is-invalid'=>$errors->has($field->attribute)])
    name="{{$field->attribute}}{{$field->multiple?"[]":""}}"
    @required($field->isRequired())
    id="inputFor{{$field->attribute}}"
    data-placeholder="{{$field->showLabel()}}"
    {{$field->multiple?'multiple':''}}

>
    <option></option>
    @foreach($field->enum as $k=>$v)
        <option value="{{$k}}" @selected(in_array($k, $field->selected))>{{$v}}</option>
    @endforeach
</select>
@error($field->attribute)
<div class="invalid-feedback">
    {{$message}}
</div>
@enderror
