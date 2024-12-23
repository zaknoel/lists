@extends(config("lists.layout"))
@section("title", $title??"")
@section("page-title", $pageTitle??"")
@section("buttons")
    <a class="btn btn-info" href="{{route("lists", $list)}}"> <i class="fa fa-arrow-circle-left me-2"></i>Назад к списку</a>
    @if($item->id)
        <a class="btn btn-info" href="{{route("lists_detail", ["list"=>$list, "item"=>$item])}}"> <i class="fa fa-arrow-circle-right me-2"></i>Детальная страница</a>
    @endif
@endsection
@section("content")
            <div class="card">
                <form method="post" enctype="multipart/form-data" class="{{$item->id?'edit_form':'add_form'}}">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            @foreach($fields as $field)
                                <div class="col-lg-{{$field->width}}" id="col-{{$field->attribute}}">
                                    <div class="form-group mb-4">
                                        <label for="inputFor{{$field->attribute}}"
                                               class="form-label fw-semibold">
                                            {!! $field->showLabel() !!}
                                            @if($field->isRequired())
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        {!! $field->show() !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="form-group text-center">
                            <a href="{{route("lists", $list)}}"
                               class="btn btn-dark rounded-pill px-4 me-3 waves-effect waves-light">
                                Отмена
                            </a>
                            <button type="submit" class="btn btn-info rounded-pill px-4 waves-effect waves-light">
                                <i class="ti ti-device-floppy me-1 fs-4"></i>
                                Сохранить
                            </button>

                        </div>
                    </div>
                </form>
            </div>

@endsection
@push('styles')

@endpush
@push("scripts")
    {!! $scripts !!}
    <script src="/vendor/lists/jquery.repeater/jquery.repeater.min.js?t=2324"></script>
    <script>
        $(".repeater").repeater({
            show: function () {
                $(this).slideDown();
            },
            hide: function (remove) {
                if (confirm("Вы уверены, что хотите удалить этот элемент?")) {
                    $(this).slideUp(remove);
                }
            },
        });
    </script>
@endpush
