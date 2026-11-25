<?php

return [

    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'new' => 'New',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'export' => 'Export to Excel',
        'settings' => 'Settings',
        'filter' => 'Filter',
        'search' => 'Search',
    ],

    'messages' => [
        'created' => 'Successfully created',
        'updated' => 'Successfully updated!',
        'deleted' => 'Item deleted successfully!',
        'options_saved' => 'Settings saved!',
        'delete_confirm' => 'Are you sure you want to delete this item?',
        'bulk_confirm' => 'Are you sure? This action cannot be undone.',
        'bulk_success' => 'Action completed successfully.',
        'bulk_deleted' => 'Selected items deleted successfully.',
    ],

    'errors' => [
        'unauthorized' => 'Access denied',
        'not_found' => 'Item not found',
        'scope_switched' => 'Project switched, some data may be unavailable.',
        'action_not_found' => 'Action not found!',
        'action_failed' => 'Error executing action',
        'page_not_found' => 'Page not found',
        'component_not_found' => 'Component not found: :list. Create file: :file',
        'component_invalid' => 'Component is misconfigured: :list',
        'export_limit_exceeded' => 'Export contains too many rows (:count). Maximum allowed: :max.',
    ],

    'export' => [
        'queued' => 'Export is being processed. The file will be saved to the server.',
        'queued_rows' => 'Export contains :count rows and has been queued for background processing.',
        'pending' => 'Export in progress…',
        'ready' => 'Export ready:',
        'failed' => 'Export failed:',
        'download' => 'Download',
        'dismiss' => 'Dismiss',
        'processing' => 'Preparing the file…',
        'cancelled' => 'Export cancelled:',
        'open' => 'Download as Excel',
        'modal_title' => 'Export list',
        'columns' => 'Fields to export',
        'select_all' => 'Select all',
        'deselect_all' => 'Clear all',
        'format' => 'File format',
        'format_xlsx' => 'Excel (.xlsx)',
        'format_csv' => 'CSV (.csv)',
        'filters_hint' => 'The file includes data matching the current list filters.',
        'start' => 'Generate file',
        'cancel' => 'Cancel export',
        'close' => 'Close',
        'preparing' => 'The file is being generated. You can close this window — the export continues in the background.',
        'queue_wait' => 'Waiting in queue…',
        'rows_progress' => 'Processed :processed of :total rows',
        'rows_unknown' => 'Rows processed: :processed',
        'done_title' => 'File is ready',
        'failed_title' => 'Could not prepare the file',
        'cancelled_title' => 'Export cancelled',
        'min_one_column' => 'Select at least one field.',
        'start_failed' => 'Could not start the export.',
    ],

    'table' => [
        'id' => 'ID',
        'actions' => 'Actions',
        'no_results' => 'No results',
        'loading' => 'Loading...',
        'all' => 'All',
    ],

    'filter' => [
        'yes' => 'Yes',
        'no' => 'No',
        'all' => 'All',
    ],

    'fields' => [
        'validation' => [
            'email' => 'Invalid email address',
            'boolean' => 'Invalid value',
            'numeric' => 'Invalid number',
            'file' => 'Invalid file',
            'image' => 'Invalid image file',
            'date' => 'Invalid date',
            'password_min' => 'Password must be at least 8 characters long.',
            'required_array' => 'Must be an array',
        ],
        'file' => [
            'download' => 'Download file',
        ],
        'location' => [
            'map' => 'Show on map',
        ],
    ],

    'validation' => [
        'required' => 'The :attribute field is required',
    ],

];
