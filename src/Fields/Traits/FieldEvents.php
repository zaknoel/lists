<?php

namespace Zak\Lists\Fields\Traits;

use Closure;

trait FieldEvents
{
    protected ?Closure $listDisplayCallback = null;

    protected ?Closure $detailDisplayCallback = null;

    protected ?Closure $onFillFormCallback = null;

    protected ?Closure $onSaveFormCallback = null;

    protected ?Closure $filterCallback = null;

    protected function eventOnShowList()
    {
        if (is_callable($this->listDisplayCallback)) {
            call_user_func($this->listDisplayCallback, $this);
        }
    }
    public function showDetailAsIndex()
    {
        $this->detailDisplayCallback=$this->listDisplayCallback;
        return $this;
    }

    protected function eventOnShowDetail()
    {
        if (is_callable($this->detailDisplayCallback)) {
            call_user_func($this->detailDisplayCallback, $this);
        }
    }

    protected function eventOnFillForm()
    {
        if (is_callable($this->onFillFormCallback)) {
            call_user_func($this->onFillFormCallback, $this);
        }
    }

    protected function eventOnSaveForm($item, $data)
    {
        if (is_callable($this->onSaveFormCallback)) {
            return call_user_func($this->onSaveFormCallback, $item, $data);
        }
        return $data;
    }

    public function onShowList(Closure $closure): static
    {
        $this->listDisplayCallback = $closure;

        return $this;
    }

    public function onShowDetail(Closure $closure): static
    {
        $this->detailDisplayCallback = $closure;

        return $this;
    }

    public function onFillForm(Closure $closure): static
    {
        $this->onFillFormCallback = $closure;

        return $this;
    }

    public function onSaveForm(Closure $closure): static
    {
        $this->onSaveFormCallback = $closure;

        return $this;
    }

    public function onBeforeFilter(Closure $closure): static
    {
        $this->filterCallback = $closure;

        return $this;
    }

    protected function eventBeforeFilter($query)
    {
        if (is_callable($this->filterCallback)) {
            call_user_func($this->filterCallback, $query, $this);
        }
    }
}
