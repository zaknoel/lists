@php
    /** @var \Zak\Lists\Services\ExportService $exportService */
    $exportService = app(\Zak\Lists\Services\ExportService::class);
    $exportFields = $exportService->exportableFields($component);
    $exportDefaults = $exportService->defaultColumns($component);
    $exportFormats = (array) config('lists.export_formats', ['xlsx', 'csv']);
    $exportText = [
        'processing' => __('lists.export.processing'),
        'queueWait' => __('lists.export.queue_wait'),
        'preparing' => __('lists.export.preparing'),
        'doneTitle' => __('lists.export.done_title'),
        'failedTitle' => __('lists.export.failed_title'),
        'cancelledTitle' => __('lists.export.cancelled_title'),
        'minOneColumn' => __('lists.export.min_one_column'),
        'startFailed' => __('lists.export.start_failed'),
        'rowsProgress' => __('lists.export.rows_progress'),
        'rowsUnknown' => __('lists.export.rows_unknown'),
    ];
@endphp
<div class="modal fade" id="export-modal" tabindex="-1" aria-labelledby="export-modal-title" aria-hidden="true"
     data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="export-modal-title">
                    <i class="ti ti-file-spreadsheet me-1"></i>{{ __('lists.export.modal_title') }}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('lists.export.close') }}"></button>
            </div>

            {{-- Step 1: choose columns and format --}}
            <div data-export-step="form">
                <div class="modal-body">
                    <div class="alert alert-danger py-2 d-none" data-export-form-error role="alert"></div>

                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 me-auto">{{ __('lists.export.columns') }}</h6>
                        <a href="javascript:void(0)" class="me-3" data-export-select="all">{{ __('lists.export.select_all') }}</a>
                        <a href="javascript:void(0)" data-export-select="none">{{ __('lists.export.deselect_all') }}</a>
                    </div>
                    <div class="row g-2 mb-4">
                        @foreach($exportFields as $exportField)
                            <div class="col-12 col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           id="export-col-{{ $loop->index }}"
                                           data-export-column
                                           value="{{ $exportField->attribute }}"
                                           @checked(in_array($exportField->attribute, $exportDefaults, true))>
                                    <label class="form-check-label" for="export-col-{{ $loop->index }}">
                                        {{ $exportField->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <h6 class="mb-2">{{ __('lists.export.format') }}</h6>
                    <div class="mb-3">
                        @foreach($exportFormats as $exportFormat)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="export-format"
                                       id="export-format-{{ $exportFormat }}" value="{{ $exportFormat }}"
                                       @checked($loop->first)>
                                <label class="form-check-label" for="export-format-{{ $exportFormat }}">
                                    {{ __('lists.export.format_'.$exportFormat) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-muted mb-0"><small>{{ __('lists.export.filters_hint') }}</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('lists.export.close') }}
                    </button>
                    <button type="button" class="btn btn-dark" data-export-start>
                        <i class="ti ti-player-play me-1"></i>{{ __('lists.export.start') }}
                    </button>
                </div>
            </div>

            {{-- Step 2: live progress / result --}}
            <div data-export-step="progress" class="d-none">
                <div class="modal-body text-center py-4">
                    <div class="mb-3" data-export-icon></div>
                    <h5 class="mb-1" data-export-title></h5>
                    <p class="text-muted mb-3" data-export-subtitle></p>
                    <div class="progress mb-2" style="height: 14px;" data-export-progress-wrap>
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark"
                             role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"
                             data-export-bar></div>
                    </div>
                    <small class="text-muted" data-export-counter></small>
                    <div class="alert alert-danger text-start mt-3 mb-0 d-none" data-export-error role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto" data-export-cancel>
                        {{ __('lists.export.cancel') }}
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('lists.export.close') }}
                    </button>
                    <a href="#" class="btn btn-success d-none" data-export-download>
                        <i class="ti ti-download me-1"></i>{{ __('lists.export.download') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<a href="javascript:void(0)" class="d-none" id="export-modal-trigger" data-bs-toggle="modal"
   data-bs-target="#export-modal" aria-hidden="true" tabindex="-1"></a>

@push('scripts')
    <script>
        window.listsExport = (function () {
            const POLL_INTERVAL_MS = 1500;
            const startUrl = @json(route('lists_export_start', $list));
            const csrfToken = @json(csrf_token());
            const text = @json($exportText);

            const modalEl = document.getElementById('export-modal');
            const stepForm = modalEl.querySelector('[data-export-step="form"]');
            const stepProgress = modalEl.querySelector('[data-export-step="progress"]');
            const formError = modalEl.querySelector('[data-export-form-error]');
            const startBtn = modalEl.querySelector('[data-export-start]');
            const cancelBtn = modalEl.querySelector('[data-export-cancel]');
            const downloadBtn = modalEl.querySelector('[data-export-download]');
            const bar = modalEl.querySelector('[data-export-bar]');
            const barWrap = modalEl.querySelector('[data-export-progress-wrap]');
            const titleEl = modalEl.querySelector('[data-export-title]');
            const subtitleEl = modalEl.querySelector('[data-export-subtitle]');
            const counterEl = modalEl.querySelector('[data-export-counter]');
            const iconEl = modalEl.querySelector('[data-export-icon]');
            const errorEl = modalEl.querySelector('[data-export-error]');

            let current = null;
            let pollTimer = null;

            const icons = {
                active: '<div class="spinner-border text-dark" role="status"></div>',
                done: '<i class="ti ti-circle-check text-success" style="font-size: 3rem;"></i>',
                failed: '<i class="ti ti-alert-circle text-danger" style="font-size: 3rem;"></i>',
                cancelled: '<i class="ti ti-ban text-muted" style="font-size: 3rem;"></i>',
            };

            function showStep(step) {
                stepForm.classList.toggle('d-none', step !== 'form');
                stepProgress.classList.toggle('d-none', step !== 'progress');
            }

            function stopPolling() {
                if (pollTimer) {
                    clearTimeout(pollTimer);
                    pollTimer = null;
                }
            }

            function formatRows(template, data) {
                return template
                    .replace(':processed', Number(data.processed_rows).toLocaleString())
                    .replace(':total', Number(data.total_rows).toLocaleString());
            }

            function render(data) {
                current = data;
                const active = !data.finished;

                barWrap.classList.toggle('d-none', !active);
                cancelBtn.classList.toggle('d-none', !active);
                downloadBtn.classList.toggle('d-none', data.status !== 'done');
                errorEl.classList.toggle('d-none', data.status !== 'failed' || !data.error);
                errorEl.textContent = data.error || '';

                if (data.status === 'done') {
                    iconEl.innerHTML = icons.done;
                    titleEl.textContent = text.doneTitle;
                    subtitleEl.textContent = data.filename;
                    downloadBtn.href = data.download_url;
                    counterEl.textContent = formatRows(text.rowsProgress, data);
                    return;
                }

                if (data.status === 'failed') {
                    iconEl.innerHTML = icons.failed;
                    titleEl.textContent = text.failedTitle;
                    subtitleEl.textContent = data.filename;
                    counterEl.textContent = '';
                    return;
                }

                if (data.status === 'cancelled') {
                    iconEl.innerHTML = icons.cancelled;
                    titleEl.textContent = text.cancelledTitle;
                    subtitleEl.textContent = data.filename;
                    counterEl.textContent = '';
                    return;
                }

                iconEl.innerHTML = icons.active;
                titleEl.textContent = data.status === 'pending' ? text.queueWait : text.processing;
                subtitleEl.textContent = text.preparing;
                bar.style.width = Math.max(data.percent, data.status === 'pending' ? 3 : 5) + '%';
                bar.setAttribute('aria-valuenow', data.percent);
                counterEl.textContent = data.total_rows > 0
                    ? formatRows(text.rowsProgress, data) + ' (' + data.percent + '%)'
                    : formatRows(text.rowsUnknown, data);
            }

            function poll() {
                if (!current || current.finished) {
                    return;
                }

                fetch(current.status_url, {
                    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        render(data);
                        schedulePoll();
                    })
                    .catch(function () {
                        schedulePoll();
                    });
            }

            function schedulePoll() {
                stopPolling();

                if (current && !current.finished) {
                    pollTimer = setTimeout(poll, POLL_INTERVAL_MS);
                }
            }

            function showFormError(message) {
                formError.textContent = message;
                formError.classList.remove('d-none');
            }

            function extractError(json) {
                if (json && json.errors) {
                    const first = Object.values(json.errors)[0];

                    return Array.isArray(first) ? first[0] : String(first);
                }

                return (json && json.message) || text.startFailed;
            }

            function start() {
                formError.classList.add('d-none');

                const columns = Array.from(modalEl.querySelectorAll('[data-export-column]:checked'))
                    .map(function (input) { return input.value; });

                if (columns.length === 0) {
                    showFormError(text.minOneColumn);

                    return;
                }

                const format = modalEl.querySelector('input[name="export-format"]:checked').value;
                const body = new FormData();
                body.append('format', format);
                body.append('query', window.location.search.replace(/^\?/, ''));
                columns.forEach(function (column) { body.append('columns[]', column); });

                startBtn.disabled = true;

                fetch(startUrl, {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) {
                        return response.json().then(function (json) {
                            return {ok: response.ok, json: json};
                        });
                    })
                    .then(function (result) {
                        if (!result.ok) {
                            showFormError(extractError(result.json));

                            return;
                        }

                        render(result.json);
                        showStep('progress');
                        schedulePoll();
                    })
                    .catch(function () {
                        showFormError(text.startFailed);
                    })
                    .finally(function () {
                        startBtn.disabled = false;
                    });
            }

            function cancel() {
                if (!current || current.finished) {
                    return;
                }

                cancelBtn.disabled = true;

                fetch(current.cancel_url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        stopPolling();
                        render(data);
                    })
                    .finally(function () {
                        cancelBtn.disabled = false;
                    });
            }

            function open() {
                if (current && current.finished) {
                    current = null;
                }

                showStep(current ? 'progress' : 'form');
                formError.classList.add('d-none');
                document.getElementById('export-modal-trigger').click();
                schedulePoll();
            }

            modalEl.querySelectorAll('[data-export-select]').forEach(function (link) {
                link.addEventListener('click', function () {
                    const checked = link.getAttribute('data-export-select') === 'all';
                    modalEl.querySelectorAll('[data-export-column]').forEach(function (input) {
                        input.checked = checked;
                    });
                });
            });

            startBtn.addEventListener('click', start);
            cancelBtn.addEventListener('click', cancel);
            modalEl.addEventListener('hidden.bs.modal', stopPolling);

            downloadBtn.addEventListener('click', function () {
                current = null;
            });

            return {open: open};
        })();
    </script>
@endpush
