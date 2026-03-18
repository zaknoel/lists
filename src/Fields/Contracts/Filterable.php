<?php

namespace Zak\Lists\Fields\Contracts;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Контракт для полей с поддержкой фильтрации.
 */
interface Filterable
{
    /**
     * Применяет фильтр к запросу и/или инициализирует значение фильтра.
     *
     * @param  Builder|false  $query
     */
    public function generateFilter(mixed $query = false): mixed;

    /**
     * Возвращает строковое представление активного значения фильтра.
     */
    public function filteredValue(): string;

    /**
     * Возвращает View с содержимым поля фильтра.
     *
     * @return View|string
     */
    public function filterContent(): mixed;

    /**
     * Возвращает View с обёрткой фильтра.
     *
     * @return View|string
     */
    public function showFilter(): mixed;
}
