@php use Zak\Lists\Fields\Select@endphp
@php /**@var Select $field*/ @endphp
<select
    @class(["form-control simple_select"])
    name="value[]"
    data-placeholder="Все"
    multiple
>
    <option></option>
    @foreach($field->enum as $k=>$v)
        <option value="{{$k}}" @selected(array_key_exists($k,$field->filter_value??[]))>{{$v}}</option>
    @endforeach
</select>

