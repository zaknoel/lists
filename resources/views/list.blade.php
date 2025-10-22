@extends(config("lists.layout"))
@section("title", $component->getLabel())
@section("page-title", $component->getLabel())
@section("buttons")
    @if($component->userCanAdd())
        <a class="btn btn-info" href="{{$component->getRoute('lists_add', $list)}}">
            <i class="fa fa-plus me-2"></i>
            {{$component->getCustomLabel("add")??'Добавить новый '.$component->getSingleLabel()}}
        </a>
    @endif
    @if($component->customButtons)
        {!! $component->customButtons !!}
    @endif
@endsection
@section("content")
    <section class="datatables" x-data="{bulk_action:[]}">
        @if($filters)
            <div class="card">
                <div class="card-header bg-info d-flex align-items-center">
                    <h4 class="card-title text-white mb-0">Фильтр</h4>
                    <div class="card-actions cursor-pointer ms-auto d-flex button-group">
                        <a class="link text-white d-flex align-items-center" data-action="collapse"><i
                                class="ti ti-minus fs-6"></i></a>
                        <a class="mb-0 btn-minimize px-2 cursor-pointer text-white link d-flex align-items-center"
                           data-action="expand"><i class="ti ti-arrows-maximize fs-6"></i></a>
                        <a class="mb-0 link d-flex text-white align-items-center pe-0 cursor-pointer"
                           data-action="close" onclick="ClearFilter()">
                            <i class="ti ti-x fs-6"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($filters as $filter)
                            {!! $filter->showFilter()!!}
                        @endforeach
                    </div>
                    <div class="text-end mt-3">
                        <a class="text-warning fs-2" href="{{route('lists', $list)}}"><i class="ti ti-reset"></i>
                            Сбросить фильтр</a>
                    </div>
                </div>
            </div>
        @endif
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table
                        width="100%"
                        style="width: 100%"
                        id="datatable"
                        class="table border table-striped table-bordered display text-nowrap"
                    >
                        <thead>
                        <!-- start row -->
                        <tr>
                            @if($component->bulkActions)
                                <th>
                                    <input type="checkbox" id="select-all-bulk"
                                          x-on:click="
                                              if($event.target.checked){
                                                  bulk_action = Array.from(document.querySelectorAll('.bulk-action-checkbox')).map(el=>el.value)
                                              }else{
                                                  bulk_action = []
                                              }"

                                    >
                                </th>
                            @endif
                            @if($component->getActions())
                                <th>
                                    <a href="javascript:void(0)"
                                       data-bs-toggle="modal"
                                       data-bs-target="#columns-modal"><i class="fa fa-cog"></i></a>
                                </th>
                            @endif
                            @foreach($fields as $field)
                                <th>{{$field->showLabel()}}</th>
                            @endforeach
                        </tr>
                        <!-- end row -->
                        </thead>
                        <tbody>
                        <!-- start row -->
                        </tbody>
                        <tfoot>
                        <!-- end row -->
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </section>
    <div
        id="columns-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="columns-modal"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-scrollable modal-lg">

            <form class="modal-content" action="{{route("lists_option", $list)}}" method="post">
                @csrf
                <div
                    class="modal-header d-flex align-items-center"
                >
                    <h4 class="modal-title" id="myModalLabel">
                        Настройка списка
                    </h4>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>
                <div class="modal-body">
                    <h6 class="mb-3">Показать колонки</h6>
                    <div class="row">
                        @foreach($component->getFields() as $field)
                            @if($field->show_in_index)
                                <div class="col-lg-4 mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input secondary"
                                               type="checkbox" id="secondary{{$field->attribute}}-check"
                                               value="1"
                                               name="columns[{{$field->attribute}}]"
                                            @checked(!$component->options->value["columns"] || in_array($field->attribute, $component->options->value["columns"]))
                                        >
                                        <label class="form-check-label"
                                               for="secondary{{$field->attribute}}-check">{{$field->showLabel()}}</label>
                                    </div>
                                </div>

                            @endif
                        @endforeach
                    </div>
                    <h6 class="mb-3">Показать фильтры</h6>
                    <div class="row" id="filters">
                        @foreach($component->getFields() as $field)
                            @if($field->show_in_index && $field->filterable)
                                <div class="col-lg-4 mb-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input secondary"
                                               type="checkbox" id="secondary_filter_{{$field->attribute}}-check"
                                               value="1"
                                               name="filters[{{$field->attribute}}]"
                                            @checked(in_array($field->attribute, $component->options->value["filters"]))
                                        >
                                        <label class="form-check-label"
                                               for="secondary_filter_{{$field->attribute}}-check">{{$field->showLabel()}}</label>
                                    </div>
                                </div>

                            @endif
                        @endforeach
                    </div>
                    <h6 class="mb-3 mt-3">Сортировка колонки</h6>
                    <ol class="sort list-group list-group-numbered" style="max-width: 500px;">
                        @foreach($component->getFields() as $field)
                            @if($field->show_in_index)
                                <li class="list-group-item cursor-pointer" style="cursor: pointer">
                                    <input type="hidden" name="sort[]" value="{{$field->attribute}}">
                                    {{$field->showLabel()}}
                                </li>
                            @endif
                        @endforeach
                    </ol>


                </div>
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light-danger text-danger font-medium waves-effect"
                        data-bs-dismiss="modal"
                    >
                        Отмена
                    </button>
                    <button
                        type="submit"
                        class="btn btn-info font-medium waves-effect"
                    >
                        Сохранить
                    </button>
                </div>
            </form>

            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection
@push('styles')
    <link rel="stylesheet" href="/vendor/lists/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
@endpush
@push("scripts")
    <script src="/vendor/lists/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.5.1/js/buttons.print.min.js"></script>
    <script type="text/javascript" charset="utf8"
            src="https://cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json"></script>
    <script type="text/javascript" src="/vendor/lists/sortable.js"></script>
    {!! $component->scripts() !!}
    <script>
        $(function () {
            var adjustment;

            $("ol.sort").sortable({
                group: 'sort',
                pullPlaceholder: false,
                // animation on drop
                onDrop: function ($item, container, _super) {
                    var $clonedItem = $('<li/>').css({height: 0});
                    $item.before($clonedItem);
                    $clonedItem.animate({'height': $item.height()});

                    $item.animate($clonedItem.position(), function () {
                        $clonedItem.detach();
                        _super($item, container);
                    });
                },

                // set $item relative to cursor position
                onDragStart: function ($item, container, _super) {
                    var offset = $item.offset(),
                        pointer = container.rootGroup.pointer;

                    adjustment = {
                        left: pointer.left - offset.left,
                        top: pointer.top - offset.top
                    };

                    _super($item, container);
                },
                onDrag: function ($item, position) {
                    $item.css({
                        left: position.left - adjustment.left,
                        top: position.top - adjustment.top
                    });
                }
            });
        });
        const table = $("#datatable").DataTable({
            pageLength: {{(int)$length}},
            processing: true,
            serverSide: true,
            responsive: true,
            scrollX: true,
            dom: "<'row'<'col-sm-12 text-start col-md-4'f><'col-sm-12 col-md-4'l><'col-sm-12 col-md-4 text-end'B>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 mt-3 col-md-7'p>>",
            ajax: {
                url: document.documentURI, // Specify the correct URL for server-side processing
                type: 'POST', // or 'POST', depending on your server-side setup
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            },
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            columnDefs: [
                {
                    targets: 0, // The first column has index 0
                    orderable: false // Disable sorting for the first column
                }
            ],
            order: [[{{(int)$curSort[2]+$component->getSortInt()}}, '{{$curSort[1]}}']],
            columns: [
                    @if($component->bulkActions)
                {
                    data: "bulk_action_checkbox",
                    name: "bulk_action_checkbox",
                    searchable: false,
                    orderable: false,
                    width: "15px"
                },
                    @endif
                    @if($component->getActions())
                {
                    data: "action",
                    name: "action",
                    searchable: false,
                    orderable: false,
                    width: "15px"
                },
                    @endif
                    @foreach($fields as $field)
                {
                    data: "{{$field->attribute}}",
                    name: "{{$field->attribute}}",
                    searchable: {{$field->searchable?'true':'false'}},
                    orderable: {{$field->sortable?'true':'false'}},
                    {!! $field->jsOptions !!}
                },
                @endforeach
            ],
            buttons: ["excel"],
        });
        table.on('init.dt', function () {
            $('.dataTables_scrollBody').css('position', 'static');
            initBulkAction();
            //table.draw();
        });
        DataTable.ext.buttons.excel = {
            className: 'buttons-excel btn btn-dark mr-1',
            text: function (dt) {
                return '<i class="ti ti-file-spreadsheet"></i> Скачать как Excel';
            },

            action: function (e, dt, button, config) {
                window.location = '{!! getCurPageParams(['excel'=>"Y"]) !!}';
            }
        };
        function executeAction(el, action){
            const btn=$(el);

            const items = [...document.querySelectorAll('.bulk-action-checkbox:checked')].map(el => el.value);
            if(items.length===0){
                alert('Пожалуйста выберите элементы для выполнения действия.');
                return;
            }
            const confirmText = btn.attr('data-confirm');
            if(confirmText){
                if(!confirm(confirmText)){
                    return;
                }
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{!! route("lists_action", $list) !!}';
            form.style.display = 'none';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = '{{ csrf_token() }}';
            form.appendChild(token);
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);
            for (const value of items) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = "items[]";
                input.value = value;
                form.appendChild(input);
            }
            document.body.appendChild(form);
            form.submit();
        }

        function initBulkAction(){
            @if($component->bulkActions)
            $('.dt-buttons').append(`
                    <div class="btn-group ms-2" x-show="bulk_action.length">
                            <button class="btn btn-danger dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                              Выберите действие
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="">
                              @foreach($component->bulkActions as $action)
                                <li><a class="dropdown-item" data-confirm="{{$action->confirmText()}}" onclick="executeAction(this, '{{$action->key()}}'); return false" href="javascript:void(0)">{{$action->label()}}</a></li>
                              @endforeach
                            </ul>
                          </div>
            `)
            @endif
        }

        function ClearFilter() {
            $('#filters input[type="checkbox"]').prop('checked', false);
            $('#filters').parents('form').submit();
        }

        const zakFilter = {
            currentFilter: "",
            init: function () {
                $(document).mouseup(function (e) {
                    var container = $(".filter_dropdown");
                    // if the target of the click isn't the container nor a descendant of the container
                    if (!container.is(e.target) && container.has(e.target).length === 0 && !$(e.target).hasClass('select2-results__option')) {
                        container.hide();
                    }
                });
                this.currentFilter = this.getCurrentFilter();
            },
            showDropDown: function (_this) {
                $('.filter_dropdown').hide();
                $(_this).next('.filter_dropdown').show();
            },
            doFilter: function (_this) {
                const t = $(_this);
                t.parents('.filter_dropdown').hide();
                this.loadFilter();

            },
            loadFilter: function () {
                const cf = this.getCurrentFilter();
                if (cf !== this.currentFilter) {
                    location.href = cf;
                }
            },
            getCurrentFilter: function () {
                let path = location.pathname;
                let params = {}
                let filterData = {}
                $('.filter_dropdown form').each(function () {
                    const name = $(this).attr('data-name');
                    const value = $(this).serializeObject();
                    value.type = $(this).attr('data-type')
                    filterData[name] = value;
                });
                for (let i in filterData) {
                    const f = filterData[i];
                    const type = f.type;
                    switch (type) {
                        case 'text-checkbox':
                            if (f.hasOwnProperty('value')) {
                                params[i] = f.value.join("⚬");
                            }
                            break;
                        case 'text-text':
                        case 'text-email':
                            if (f.value) {
                                params[i] = f.op + '⚬' + f.value;
                            }
                            break;
                        case 'text-relation':
                        case 'text-select':
                            let vv = [];
                            for (let j in f.value) {
                                const v = f.value[j];
                                if (v) vv.push(v);
                            }
                            if (vv.length) {
                                params[i] = vv.join("⚬");
                            }
                            break;
                        case 'custom-custom':
                            let vvvv = [];
                            for (let j in f.value) {
                                const v = f.value[j];
                                if (v) vvvv.push(j + '-' + v);
                            }
                            params[i] = vvvv.join("⚬");
                            break;
                        case 'id-text':
                        case 'number-text':
                        case 'date-text':
                        case 'datetime-local-text':
                            let vvv = [];
                            if (f['value_from']) {
                                vvv.push('f' + f.value_from);
                            }
                            if (f['value_to']) {
                                vvv.push('t' + f.value_to);
                            }
                            if (vvv.length) {
                                params[i] = vvv.join("⚬");
                            }
                            break;
                        default:
                            console.log('Filter Type not found:' + type)
                    }
                }
                path += '?' + new URLSearchParams(params).toString();
                //console.log(path);
                return path;

            }
        }
        zakFilter.init()

    </script>
@endpush
