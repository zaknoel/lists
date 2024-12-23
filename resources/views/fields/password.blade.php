@php use Zak\Lists\Fields\Text; @endphp
@php /**@var Text $field*/ @endphp

    <input
        type="{{$field->getType()}}"
        @class(["form-control", 'is-invalid'=>$errors->has($field->attribute)])
        name="{{$field->attribute}}"
        value=""
        autocomplete="off"
        @required($field->isRequired())
        id="inputFor{{$field->attribute}}"
        placeholder="{{$field->showLabel()}}">
    @error($field->attribute)
    <div class="invalid-feedback">
        {{$message}}
    </div>
    @enderror


