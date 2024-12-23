<div>
    <select
        @class(["form-control ajax_sel"])
        name="value[]"
        data-placeholder="Все"
        multiple
        data-model="{{$field->model}}"
        data-field="{{$field->field}}"
        data-filter='{!! json_encode($field->filter) !!}'

    >
        <option></option>
        @foreach($field->filter_value??[] as $id=>$name)
            <option value="{{$id}}" selected>{{$name}}</option>
        @endforeach
    </select>
</div>
