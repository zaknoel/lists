@extends(config("lists.layout"))
@section("title", $title??"")
@section("page-title", $pageTitle??"")
@section("buttons")
    @if(!$frame)
        <a class="btn btn-info" href="{{route("lists", $list)}}"> <i class="fa fa-arrow-circle-left me-2"></i>Назад к
            списку</a>
        @if($item->id)
            <a class="btn btn-info" href="{{route("lists_detail", ["list"=>$list, "item"=>$item])}}"> <i
                    class="fa fa-arrow-circle-right me-2"></i>Детальная страница</a>
        @endif
    @endif
@endsection
@section("content")
    <style>
        @if($frame)
            header.app-header {
            display: none;
        }
        @endif
    </style>
    <div class="card">

        <form method="post" enctype="multipart/form-data" class="{{$item->id?'edit_form':'add_form'}}">
            @csrf
            @if($frame)
                <input type="hidden" name="frame" value="1">
            @endif
            <div class="card-body">
                @if($errors->any())
                    @foreach($errors->all() as $error)
                        <div class="alert alert-danger" role="alert">
                            {{$error}}
                        </div>
                    @endforeach
                @endif
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
                       @if($frame) onclick="window.close();return false;" @endif
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
        let win = false;
        let winData = {}

        function createRealtion(el, list, attr) {
            const url = '/lists/' + list + '/add?frame=1';
            if (win) {
                win.close();
            }
            win = window.open(url, 'ChildWindow', 'scrollbars=yes,resizable=yes,toolbar=0,location=0,status=no,menubar=0,width=800,height=600');
            win.focus();
            winData.el = $(el);
        }
        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) return; // Security check
            const data = event.data;
            if (data.action === 'frame') {
                const el = winData.el;
                const value = data.id;
                const text = data.name;
                const select = el.closest('.input-group').find('select');
                select.append(new Option(text, value, true, true)).trigger('change');
            }
        });
    </script>
@endpush
