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
    ],

    'table' => [
        'id' => 'ID',
        'actions' => 'Actions',
        'no_results' => 'No results',
        'loading' => 'Loading...',
        'all' => 'All',
    ],

    'validation' => [
        'required' => 'The :attribute field is required',
    ],

];
