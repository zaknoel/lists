<div class="d-block">
<input type="hidden"   name="{{$field->attribute}}" value="0">
<input
    type="checkbox"
    @class(["bt-switch", 'is-invalid'=>$errors->has($field->attribute)])
    @checked(old($field->attribute)??$field->value??$field->default)
    id="inputFor{{$field->attribute}}"
    name="{{$field->attribute}}"
    value="1"
    data-on-color="success"
    data-off-color="warning"
/>
    @error($field->attribute)
    <div class="invalid-feedback">
        {{$message}}
    </div>
    @enderror
</div>
@push("scripts")
    <script>
        $(function (){
            $("#inputFor{{$field->attribute}}").bootstrapSwitch("state", $("#inputFor{{$field->attribute}}").is(':checked'));
        })
    </script>

@endpush
