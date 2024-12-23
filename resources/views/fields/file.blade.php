@php use App\Zak\Component\Fields\File; @endphp
@php /**@var File $field*/ @endphp

@if($field->value)
    <div class="mb-3">
    <a href="{{Storage::url($field->value)}}" download="" >{{$field->value}}</a>
        <div class="form-check form-check-inline ms-3">
            <input class="form-check-input danger" type="checkbox" id="danger2-check{{$field->attribute}}" name="delete[{{$field->attribute}}]" value="{{$field->value}}">
            <label class="form-check-label" for="danger2-check{{$field->attribute}}">Удалить</label>
        </div>
    </div>
@endif
<input
    type="file"
    @class(["form-control", 'is-invalid'=>$errors->has($field->attribute)])
    name="{{$field->attribute}}{{$field->multiple?'[]':""}}"
    value=""
    accept="*/*"
    {{$field->multiple?'multiple="multiple"':""}}
    @required($field->isRequired())
    id="inputFor{{$field->attribute}}"
    placeholder="{{$field->showLabel()}}">
@error($field->attribute)
<div class="invalid-feedback">
    {{$message}}
</div>
@enderror
