<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Zak\Lists\Http\Contollers\ListController;

Route::middleware(config('lists.middleware', ['web', 'auth']))->group(function () {
    Route::post('/lists/{list}/option', [ListController::class, 'options'])->name('lists_option')
        ->withoutMiddleware([VerifyCsrfToken::class]);
    //add
    Route::get('/lists/{list}/add', [ListController::class, 'add_form'])->name('lists_add');
    Route::post('/lists/{list}/add', [ListController::class, 'add_save']);
    //edit
    Route::get('/lists/{list}/edit/{item}', [ListController::class, 'edit_form'])->name('lists_edit')
    ->where('item', '[0-9]+');
    Route::post('/lists/{list}/edit/{item}', [ListController::class, 'edit_save'])->where('item', '[0-9]+');

    Route::any('/lists/{list}', [ListController::class, 'list'])->name('lists')
        ->withoutMiddleware([VerifyCsrfToken::class]);
    Route::get('/lists/{list}/{item}', [ListController::class, 'detail'])->name('lists_detail')->where('item', '[0-9]+');
    Route::get('/lists/{list}/{item}/{page}', [ListController::class, 'pages'])->name('lists_pages')->where('item', '[0-9]+');
    Route::post('/lists/{list}/delete/{item}', [ListController::class, 'delete'])->name('lists_delete')->where('item', '[0-9]+');
});
