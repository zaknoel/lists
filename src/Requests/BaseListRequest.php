<?php

namespace Zak\Lists\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Zak\Lists\Component;
use Zak\Lists\Contracts\ComponentLoaderContract;

/**
 * Базовый Form Request для всех операций с листами.
 * Резолвит компонент из маршрутного параметра {list} и кэширует его.
 */
abstract class BaseListRequest extends FormRequest
{
    private ?Component $resolvedComponent = null;

    /**
     * Возвращает компонент, соответствующий параметру {list} текущего маршрута.
     */
    protected function component(): Component
    {
        if ($this->resolvedComponent === null) {
            $this->resolvedComponent = app(ComponentLoaderContract::class)
                ->resolve((string) $this->route('list'));
        }

        return $this->resolvedComponent;
    }
}
