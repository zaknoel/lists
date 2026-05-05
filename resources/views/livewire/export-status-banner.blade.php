<div wire:poll.5000ms>
    @if($exports->isNotEmpty())
        <div class="mb-3" id="lists-export-banners">
            @foreach($exports as $export)
                @if($export->status === 'pending')
                    <div class="alert alert-info d-flex align-items-center mb-2 py-2" role="alert">
                        <div class="spinner-border spinner-border-sm me-2 flex-shrink-0" role="status">
                            <span class="visually-hidden">{{ __('lists.export.pending') }}</span>
                        </div>
                        <span class="me-auto">
                            {{ __('lists.export.pending') }}
                            <strong>{{ $export->filename }}</strong>
                        </span>
                        <button type="button" class="btn-close ms-2"
                                wire:click="dismiss({{ $export->id }})"
                                aria-label="{{ __('lists.export.dismiss') }}"></button>
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
