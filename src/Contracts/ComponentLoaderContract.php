<?php

namespace Zak\Lists\Contracts;

use Zak\Lists\Component;

interface ComponentLoaderContract
{
    /**
     * Загружает компонент из файла конфигурации.
     *
     * @param  string  $list  Имя файла конфигурации без расширения
     * @param  bool  $applySortOrder  Применить пользовательский порядок колонок
     */
    public function resolve(string $list, bool $applySortOrder = false): Component;
}
