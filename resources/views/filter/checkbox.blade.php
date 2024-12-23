<div>
    <div class="form-check form-check-inline">
        <input class="form-check-input secondary" type="checkbox" id="secondary{{$field->attribute}}-check-yes"
               name="value[]" value="1"
            @checked(in_array('Да', $field->filter_value))
        >
        <label class="form-check-label" for="secondary{{$field->attribute}}-check-yes">Да</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input secondary" type="checkbox" id="secondary{{$field->attribute}}-check-no"
               name="value[]" value="0"
            @checked(in_array('Нет', $field->filter_value))
        >
        <label class="form-check-label" for="secondary{{$field->attribute}}-check-no">Нет</label>
    </div>
</div>
