<?php

use Zak\Lists\ListImport;

// config for Zaknoel/Lists
return [
    'path' => app_path('Lists/'),
    'layout' => 'layouts.app',
    'middleware' => ['web', 'auth'],
    'default_length' => 25,
    'max_length' => 250,
    'bulk_chunk_size' => 500,
    'export_chunk_size' => 500,

    /*
     * Hard row limit for an export. Larger exports are rejected before they are queued.
     * Set to 0 to disable the limit.
     */
    'max_export_rows' => 50000,

    /*
     * Deprecated: every export now runs on the queue. Kept for backward compatibility only.
     */
    'export_async_threshold' => 0,

    /*
     * Queue connection (null = application default) and queue name for export jobs.
     * Run a dedicated worker/supervisor for this queue (e.g. a Horizon supervisor with a long timeout).
     */
    'export_connection' => null,
    'export_queue' => 'exports',

    /*
     * Allowed export formats and the CSV field delimiter (semicolon opens correctly in Excel for RU locales).
     */
    'export_formats' => ['xlsx', 'csv'],
    'csv_delimiter' => ';',

    /*
     * Hours after which an export stuck in pending/processing is marked as failed by `lists:prune-exports`.
     */
    'export_stale_hours' => 3,

    /*
     * Filesystem disk and directory used by queued export jobs to store generated files.
     */
    'export_disk' => 'local',
    'export_path' => 'exports',

    /*
     * Number of days to keep async export records and their files.
     * Used by `php artisan lists:prune-exports`. Set to 0 to disable pruning.
     */
    'export_prune_days' => 7,

    /*
     * The class used to build the Excel export file. Must implement
     * Maatwebsite\Excel\Concerns\FromCollection and accept a prepared
     * flat rows array as its first constructor argument: __construct(array $rows).
     *
     * Override with a custom class to add styles, events, merged cells, etc.
     * Example: 'import_class' => \App\Services\MyExportImport::class,
     */
    'import_class' => ListImport::class,

    /*
     * API key for Yandex Maps script used by Location field.
     * Leave null to load script without the apikey query parameter.
     */
    'yandex_maps_key' => env('LISTS_YANDEX_MAPS_KEY'),
];
