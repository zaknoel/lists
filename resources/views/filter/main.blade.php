<div class="col-auto mb-3">
    <div class="dropdown-toggle me-3 mb-0" id="dropdownMenuButton{{$field->attribute}}" onclick="zakFilter.showDropDown(this)" >
        <span class="fw-bolder me-2">{{$field->showLabel()}}:</span><span class="text-muted">{{$field->filteredValue()}}</span>
    </div>
    <div class="dropdown-menu p-3 filter_dropdown" style="border: 1px solid #cecece" aria-labelledby="dropdownMenuButton{{$field->attribute}}"
         >
        <form style="min-width: 200px" data-name="{{$field->attribute}}" data-type="{{$field->type}}-{{$field->componentName()}}">
            <h6 class="mb-2">{{$field->showLabel()}}:</h6>
            {!! $field->filterContent() !!}
            <div class="text-center mt-3">
                <button type="button" class="btn btn-sm btn-success" onclick="zakFilter.doFilter(this)">Применить</button>
            </div>
        </form>
    </div>
</div>
