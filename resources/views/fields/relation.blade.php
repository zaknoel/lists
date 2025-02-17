@php use Zak\Lists\Fields\Relation; @endphp
@php /**@var Relation $field*/ @endphp
<div class="input-group">
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
    @if($field->list && $field->createButton && auth()->user()->can('create', $field->model))
        <div class="input-group-append">
            <button class="btn btn-secondary"
                     onclick="createRealtion(this, '{{$field->list}}', '{{$field->attribute}}')"
                    title="Добавить новый" style="border-top-left-radius: 0; border-bottom-left-radius: 0; height: 100%"  type="button">
                <i class="ti ti-plus"></i>
            </button>
        </div>
    @endif
</div>
@error($field->attribute)
<div class="invalid-feedback">
    {{$message}}
</div>
@enderror
