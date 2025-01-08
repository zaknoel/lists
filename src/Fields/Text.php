<?php

namespace Zak\Lists\Fields;

class Text extends Field
{
    public int $rows = 1;

    public function rows($rows)
    {
        $this->rows = $rows;

        return $this;
    }

    public function componentName(): string
    {
        return 'text';
    }

    public function type()
    {
        return 'text';
    }

    public function filteredValue(): string
    {
        return trim(implode(' | ', $this->filter_value)) ?: 'Все '; // TODO: Change the autogenerated stub
    }

    public function generateFilter($query = false)
    {
        $this->filter_value = [];
        if (request()?->has($this->attribute)) {
            $v = explode('⚬', request()?->get($this->attribute, ''));
            if (
                $this instanceof ID
                | $this instanceof Number
                | $this instanceof Date
            ) {
                foreach ($v as $k) {
                    if (str_starts_with($k, 'f')) {
                        $this->filter_value['from'] = substr($k, 1);
                        if ($query) {
                            $query->where($this->attribute, '>=', $this->filter_value['from']);
                        }
                    } elseif (str_starts_with($k, 't')) {
                        $this->filter_value['to'] = substr($k, 1);
                        if ($query) {
                            $query->where($this->attribute, '<=', $this->filter_value['to']);
                        }
                    }
                }

            } else {
                $this->filter_value = ['operator' => $v[0] ?? '=', 'value' => $v[1] ?? ''];
                if ($query) {
                    switch ($this->filter_value['operator']) {
                        case 'like':
                            $query->where($this->attribute, 'LIKE', '%'.$this->filter_value['value'].'%');
                            break;
                        case 'not_like':
                            $query->where($this->attribute, 'NOT LIKE', '%'.$this->filter_value['value'].'%');
                            break;
                        default:
                            $query->where($this->attribute, $this->filter_value['operator'],
                                $this->filter_value['value']);
                    }
                }
            }
        }

        return $query;
    }

    public function handleFill()
    {

        if ($this->multiple) {
            $isJson = in_array($item->cast[$this->attribute] ?? false, ['array', 'json']);
            if ($isJson) {
                $this->value = explode('|', $this->item->{$this->attribute}[0] ?? '');
            } else {
                $this->value = explode('|', $this->item->{$this->attribute} ?? '');
            }
        } else {
            $this->value = $this->item->{$this->attribute};
        }
    }

    public function saveHandler($item, $data)
    {
        if ($this->multiple) {
            $item->{$this->attribute} = implode('|', $data[$this->attribute] ?? []);
        } else {
            $item->{$this->attribute} = $data[$this->attribute];
        }

        return $item;
    }

    public function detailHandler()
    {
        $this->indexHandler(); // TODO: Change the autogenerated stub
    }

    public function indexHandler()
    {
        if ($this->multiple) {
            $isJson = in_array($item->cast[$this->attribute] ?? false, ['array', 'json']);
            if ($isJson) {
                $this->value = explode('|', $this->item->{$this->attribute}[0] ?? '');
            } else {
                $this->value = explode('|', $this->item->{$this->attribute} ?? '');
            }
            $this->value = implode(', ', $this->value);
        }
    }
}
