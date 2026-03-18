<?php

namespace Zak\Lists\Fields\Contracts;

use Illuminate\Contracts\View\View;

/**
 * Контракт для полей с поддержкой отображения.
 */
interface Displayable
{
    /**
     * Возвращает значение поля для строки таблицы списка.
     * Если поле помечено defaultAction и передан экшен — оборачивает в ссылку.
     */
    public function showIndex(mixed $item, string $list, mixed $action = null): mixed;

    /**
     * Возвращает значение поля для детальной страницы.
     */
    public function showDetail(): mixed;

    /**
     * Возвращает View для отображения поля на форме редактирования/создания.
     *
     * @return View|string
     */
    public function show(): mixed;

    /**
     * Инициализирует состояние поля для отображения в форме редактирования/создания.
     */
    public function showEdit(): void;
}
