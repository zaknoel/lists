<?php

use Illuminate\Support\Facades\Route;
use Zak\Lists\Http\Controllers\ListController;

Route::middleware(config('lists.middleware', ['web', 'auth']))->group(function () {

    // Настройки пользователя (порядок колонок, фильтры)
    Route::post('/lists/{list}/option', [ListController::class, 'options'])->name('lists_option');

    // Групповые действия (bulk actions)
    Route::post('/lists/{list}/action', [ListController::class, 'bulkAction'])->name('lists_action');

    // Создание
    Route::get('/lists/{list}/add', [ListController::class, 'create'])->name('lists_add');
    Route::post('/lists/{list}/add', [ListController::class, 'store'])->name('lists_store');

    // Редактирование
    Route::get('/lists/{list}/{item}/edit', [ListController::class, 'edit'])
        ->name('lists_edit')
        ->where('item', '[0-9]+');

    Route::match(['POST', 'PUT'], '/lists/{list}/{item}/edit', [ListController::class, 'update'])
        ->name('lists_update')
        ->where('item', '[0-9]+');

    // Удаление (DELETE + POST-spoofing для HTML-форм)
    Route::delete('/lists/{list}/{item}', [ListController::class, 'destroy'])
        ->name('lists_delete')
        ->where('item', '[0-9]+');

    // Кастомные страницы (вкладки)
    Route::get('/lists/{list}/{item}/{page}', [ListController::class, 'pages'])
        ->name('lists_pages')
        ->where('item', '[0-9]+');

    // Индекс (DataTables + Excel — работает через Any из-за DataTables AJAX POST)
    Route::any('/lists/{list}', [ListController::class, 'index'])->name('lists');

    // Детальная страница
    Route::get('/lists/{list}/{item}', [ListController::class, 'show'])
        ->name('lists_detail')
        ->where('item', '[0-9]+');

});
