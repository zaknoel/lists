@php @endphp
@extends(config("lists.layout"))
@section("title", $item->name??"")
@section("page-title", $item->name??"")
@section("buttons")
    <a class="btn btn-info" href="{{route("lists", $list)}}"> <i class="fa fa-arrow-circle-left me-2"></i>Назад к списку</a>
    @if(is_callable($component->callCustomDetailButtons))
        {!! $component->callCustomDetailButtons($item) !!}
    @endif
    @if($component->userCanEdit($item))
        <a href="{{route("lists_edit", ["list"=>$list, "item"=>$item->id])}}" class="btn btn-primary">
            <i class="ti ti-edit fs-5"></i>
            Редактировать
        </a>
    @endif
    @if($component->userCanDelete($item))
        <form method="post" class="d-inline" action="{{route("lists_delete", ["list"=>$list, "item"=>$item->id])}}">
            @csrf
            <button onclick="return confirm('Вы уверены, что хотите удалить этот элемент?')" class="btn btn-danger">
                <i class="ti ti-trash fs-5"></i>
                Удалить
            </button>
        </form>
    @endif
@endsection
@section("content")

    <div class="card">
        <ul class="nav nav-pills user-profile-tab justify-content-start bg-light-info rounded-tr-2xl z-index-5"
            id="pills-tab">
            <li class="nav-item">
                <a
                    href="{{route("lists_detail", ["list"=>$list, 'item'=>$item])}}"
                    class="nav-link position-relative rounded-0 d-flex align-items-center
                    justify-content-center bg-transparent fs-3 py-6 {{!isset($page)?'active':''}}">
                    <span class="d-none d-md-block">Общая информация</span>
                </a>
            </li>
            @foreach($pages as $k=>$v)
                <li class="nav-item">
                    <a
                        href="{{route("lists_pages", ["list"=>$list, 'item'=>$item, "page"=>$k])}}"
                        class="nav-link position-relative rounded-0 d-flex align-items-center
                    justify-content-center bg-transparent fs-3 py-6 {{isset($page) && $page===$k?'active':''}}">
                        <span class="d-none d-md-block">{{$v["title"]}}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="card-body">
            @if(isset($view))
                {!! $view !!}
            @else
                <div class="row">
                    @foreach($fields as $field)
                        <div class="col-lg-6">
                            <div class="form-group row">
                                <label class="control-label  text-end col-md-3 fw-bold ">{{$field->showLabel()}}
                                    :</label>
                                <div class="col-md-9">
                                    <p class="form-control-static">
                                        {!! $field->showDetail($item) !!}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <!--/row-->

        </div>
    </div>
    <hr>
    {{--<div class="form-actions">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-offset-3 col-md-9">
                            <form method="post" action="{{route("lists_delete", ["list"=>$list, "item"=>$item->id])}}" >
                                @csrf
                            <a href="{{route("lists_edit", ["list"=>$list, "item"=>$item->id])}}" class="btn btn-primary">
                                <i class="ti ti-edit fs-5"></i>
                                Редактировать
                            </a>

                            <button onclick="return confirm('Вы уверены, что хотите удалить этот элемент?')" class="btn btn-danger">
                                <i class="ti ti-trash fs-5"></i>
                                Удалить
                            </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"></div>
            </div>
        </div>
    </div>--}}
    <!-- ---------------------
                                start Form with view only
                            ---------------- -->

@endsection
@push('styles')

@endpush
@push("scripts")
    {!! $component->scripts() !!}
@endpush
