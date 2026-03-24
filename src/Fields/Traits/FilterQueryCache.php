<?php

namespace Zak\Lists\Fields\Traits;

/**
 * Кэш уровня запроса для повторяющихся lookup-запросов в filter полях (Relation, BelongToMany).
 *
 * Статический массив сбрасывается при завершении PHP-процесса (каждый FPM-запрос — чистое состояние).
 * В тестах используйте FilterQueryCache::clearMemo() в tearDown при необходимости.
 */
trait FilterQueryCache
{
    /** @var array<string, array<int|string, string>> Кэш результатов pluck-запросов */
    protected static array $filterMemo = [];

    /**
     * Возвращает результат `pluck('name', 'id')` для модели с заданным набором ID,
     * используя статический кэш во избежание дублирующихся запросов на один запрос.
     *
     * @param  class-string  $modelClass
     * @param  array<int, string>  $ids
     * @return array<int|string, string>
     */
    protected function cachedPluckNames(string $modelClass, array $ids): array
    {
        sort($ids);
        $cacheKey = $modelClass.':'.implode(',', $ids);

        if (! isset(static::$filterMemo[$cacheKey])) {
            static::$filterMemo[$cacheKey] = $modelClass::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all();
        }

        return static::$filterMemo[$cacheKey];
    }

    /**
     * Очищает статический кэш (удобно в тестах).
     */
    public static function clearMemo(): void
    {
        static::$filterMemo = [];
    }
}
