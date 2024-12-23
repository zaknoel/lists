@php use Zak\Lists\Fields\Text; @endphp
@php /**@var Text $field*/ @endphp
@if($field->multiple)
    <div class="repeater mb-3">

        <div data-repeater-list="{{$field->attribute}}">
            @foreach(old($field->attribute, $field->value)??[""] as $key=>$value)
                <div data-repeater-item class="row mb-3">
                    <div class="col-md-10">
                        <input
                            type="{{$field->getType()}}"
                            @class(["form-control", 'is-invalid'=>$errors->has($field->attribute."[".$key."]")])
                            name="i"
                            value="{{array_key_exists($key, old($field->attribute)??[])?old($field->attribute)[$key]:$value}}"
                            @required($field->isRequired())
                            id="inputFor{{$field->attribute}}{{$key}}"
                            placeholder="{{$field->showLabel()}}"
                        >
                        @error($field->attribute."[".$key."]")
                        <div class="invalid-feedback">
                            {{$message}}
                        </div>
                        @enderror

                    </div>
                    <div class="col-md-2">
                        <button data-repeater-delete="" class="btn btn-danger waves-effect waves-light"
                                type="button">
                            <i class="ti ti-circle-x fs-5"></i>
                        </button>
                    </div>
                </div>
            @endforeach

        </div>
        <button type="button" data-repeater-create="" class="btn btn-sm btn-info waves-effect waves-light">
            <div class="d-flex align-items-center">
                Добавить еще
                <i class="ti ti-circle-plus ms-1 fs-5"></i>
            </div>
        </button>
    </div>
@else
    <input
        type="{{$field->getType()}}"
        @class(["form-control", 'is-invalid'=>$errors->has($field->attribute)])
        name="{{$field->attribute}}"
        value="{{old($field->attribute, ($field->value??$field->default))}}"
        @required($field->isRequired())
        id="inputFor{{$field->attribute}}"
        placeholder="{{$field->showLabel()}}">
    @error($field->attribute)
    <div class="invalid-feedback">
        {{$message}}
    </div>
    @enderror

@endif
