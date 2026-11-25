<div @if($hasActive) wire:poll.2s @else wire:poll.30s @endif>
    @if($exports->isNotEmpty())
        <div class="mb-3" id="lists-export-banners">
            @foreach($exports as $export)
                @if(in_array($export->status, ['pending', 'processing'], true))
                    <div class="alert alert-info mb-2 py-2" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-2 flex-shrink-0" role="status">
                                <span class="visually-hidden">{{ __('lists.export.pending') }}</span>
                            </div>
                            <span class="me-auto">
                                {{ $export->status === 'pending' ? __('lists.export.queue_wait') : __('lists.export.processing') }}
                                <strong>{{ $export->filename }}</strong>
                                <small class="text-muted">({{ strtoupper($export->format) }})</small>
                            </span>
                            <button type="button" class="btn-close ms-2"
                                    wire:click="dismiss({{ $export->id }})"
                                    aria-label="{{ __('lists.export.dismiss') }}"></button>
                        </div>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: {{ max($export->progress_percent, 3) }}%"></div>
                        </div>
                        @if($export->total_rows > 0)
                            <small class="text-muted">
                                {{ __('lists.export.rows_progress', ['processed' => number_format($export->processed_rows), 'total' => number_format($export->total_rows)]) }}
                            </small>
                        @endif
                    </div>
                @elseif($export->status === 'done')
                    <div class="alert alert-success d-flex align-items-center mb-2 py-2" role="alert">
                        <i class="ti ti-file-spreadsheet me-2 fs-5 flex-shrink-0"></i>
                        <span class="me-auto">
                            {{ __('lists.export.ready') }}
                            <strong>{{ $export->filename }}</strong>
                        </span>
                        <a href="{{ route('lists_export_download', $export->id) }}"
                           class="btn btn-sm btn-success ms-2"
                           wire:click="dismiss({{ $export->id }})">
                            <i class="ti ti-download me-1"></i>{{ __('lists.export.download') }}
                        </a>
                        <button type="button" class="btn-close btn-close-white ms-2"
                                wire:click="dismiss({{ $export->id }})"
                                aria-label="{{ __('lists.export.dismiss') }}"></button>
                    </div>
                @elseif($export->status === 'cancelled')
                    <div class="alert alert-secondary d-flex align-items-center mb-2 py-2" role="alert">
                        <i class="ti ti-ban me-2 fs-5 flex-shrink-0"></i>
                        <span class="me-auto">
                            {{ __('lists.export.cancelled') }}
                            <strong>{{ $export->filename }}</strong>
                        </span>
                        <button type="button" class="btn-close ms-2"
                                wire:click="dismiss({{ $export->id }})"
                                aria-label="{{ __('lists.export.dismiss') }}"></button>
                    </div>
                @elseif($export->status === 'failed')
                    <div class="alert alert-danger d-flex align-items-center mb-2 py-2" role="alert">
                        <i class="ti ti-alert-circle me-2 fs-5 flex-shrink-0"></i>
                        <span class="me-auto">
                            {{ __('lists.export.failed') }}
                            <strong>{{ $export->filename }}</strong>
                            @if($export->error_message)
                                <small class="d-block text-danger-emphasis">{{ $export->error_message }}</small>
                            @endif
                        </span>
                        <button type="button" class="btn-close btn-close-white ms-2"
                                wire:click="dismiss({{ $export->id }})"
                                aria-label="{{ __('lists.export.dismiss') }}"></button>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
