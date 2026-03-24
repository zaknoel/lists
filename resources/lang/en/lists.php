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
