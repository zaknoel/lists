<?php

namespace Zak\Lists\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Zak\Lists\Component;

interface QueryContract
{
    /**
     * Строит базовый запрос для списка (с eager-load связей и callback из компонента).
     */
    public function buildIndexQuery(Component $component, Request $request): Builder;

    /**
     * Строит запрос для детального просмотра.
     */
    public function buildDetailQuery(Component $component): Builder;

    /**
     * Строит запрос для формы редактирования.
     */
    public function buildEditQuery(Component $component): Builder;

    /**
     * Ищет элемент или возвращает 404. При наличии global scope и пустом результате
     * пытается найти без scope и редиректит, если найден.
     *
     * @param  Builder  $query  Уже подготовленный запрос (с нужным query-событием)
     */
    public function findOrAbort(Component $component, Builder $query, int $id): Model;
}
