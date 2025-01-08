<?php

namespace Zak\Lists\Fields\Traits;

trait FieldFilter
{
    public string $filter_view = '';

    public $filter_value = null;

    public function filterView($view): static
    {
        $this->filter_view = $view;

        return $this;
    }

    public function filterContent()
    {
        if ($this->filter_view) {
            return view($this->filter_view, ['field' => $this]);
        }
        $view = 'lists::filter.'.$this->componentName();
        if (! view()->exists($view)) {
            // create view
            $file = resource_path('views/vendor/lists/filter/'.$this->componentName().'.blade.php');
            if (! file_exists($file)) {
                file_put_contents($file, '<div></div>');
            }
        }

        return view($view, ['field' => $this]);
    }

    abstract public function generateFilter($query = false);

    abstract public function filteredValue();

    public function showFilter()
    {
        return view('lists::filter.main', ['field' => $this]);
    }
}
