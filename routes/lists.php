<?php


use Illuminate\Support\Facades\Route;
use Zak\Lists\Http\Contollers\ListController;
Route::middleware(config('lists.middleware',['web', 'auth']))->group(function () {
    Route::post("/lists/{list}/option", [ListController::class, "options"])->name("lists_option");
    Route::get("/lists/{list}/add", [ListController::class, "add_form"])->name("lists_add");

    Route::post("/lists/{list}/add", [ListController::class, "add_save"]);


    Route::get("/lists/{list}/edit/{item}", [ListController::class, "edit_form"])->name("lists_edit");
    Route::post("/lists/{list}/edit/{item}", [ListController::class, "edit_save"]);

    Route::get("/lists/{list}", [ListController::class, "list"])->name("lists");
    Route::get("/lists/{list}/{item}", [ListController::class, "detail"])->name("lists_detail");
    Route::get("/lists/{list}/{item}/{page}", [ListController::class, "pages"])->name("lists_pages");
    Route::post("/lists/{list}/delete/{item}", [ListController::class, "delete"])->name("lists_delete");
});

