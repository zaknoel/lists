@switch($field->type)
    @case('text')
    @case('email')
        <div>
            <select class="form-control form-control-sm" name="op">
                <option
                    @selected(($field->filter_value['operator']??'')==='=')
                    value="=">равно
                </option>
                <option
                    @selected(($field->filter_value['operator']??'')==='!=')
                    value="!=">не равно
                </option>
                <option value=">"
                    @selected(($field->filter_value['operator']??'')==='>')
                >больше
                </option>
                <option value="<"
                    @selected(($field->filter_value['operator']??'')==='<')
                >
                    меньше
                </option>
                <option
                    @selected(($field->filter_value['operator']??'')==='like')
                    value="like">содержить
                </option>
                <option value="not_like"
                    @selected(($field->filter_value['operator']??'')==='not_like')
                >не содержить
                </option>
            </select>
            <input type="text" value="{{$field->filter_value['value']??''}}" class="form-control form-control-sm mt-1" name="value">
        </div>
        @break
    @case('id')
    @case('number')
        <div class="input-group mb-3">
            <span class="input-group-text">от</span>
            <input type="number"  value="{{$field->filter_value["from"]??''}}" class="form-control form-control-sm mt-1" name="value_from"
            >
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">до</span>
            <input type="number"   value="{{$field->filter_value["to"]??''}}" class="form-control form-control-sm mt-1" name="value_to">
        </div>
        @break
    @case('date')
        <div class="input-group mb-3">
            <span class="input-group-text">от</span>
            <input type="date"  value="{{$field->filter_value["from"]??''}}" class="form-control form-control-sm mt-1" name="value_from" >
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">до</span>
            <input type="date"   value="{{$field->filter_value["to"]??''}}" class="form-control form-control-sm mt-1" name="value_to">
        </div>
        @break
    @case('datetime')
        <div class="input-group mb-3">
            <span class="input-group-text">от</span>
            <input type="datetime-local"  value="{{$field->filter_value["from"]??''}}" class="form-control form-control-sm mt-1" name="value_from">
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">до</span>
            <input type="datetime-local"   value="{{$field->filter_value["to"]??''}}" class="form-control form-control-sm mt-1" name="value_to">
        </div>
        @break
@endswitch
