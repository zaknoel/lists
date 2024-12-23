@php use Zak\Lists\Fields\Relation; @endphp
@php /**@var Relation $field*/ @endphp
<select
    @class(["form-control ajax_sel", 'is-invalid'=>$errors->has($field->attribute)])
    name="{{$field->attribute}}{{$field->multiple?"[]":""}}"

    @required($field->isRequired())
    id="inputFor{{$field->attribute}}"
    data-placeholder="{{$field->showLabel()}}"
    {{$field->multiple?'multiple':''}}
    data-model="{{$field->model}}"
    data-field="{{$field->field}}"
    data-filter='{!! json_encode($field->filter) !!}'

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
