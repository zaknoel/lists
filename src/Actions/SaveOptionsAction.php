<?php

namespace Zak\Lists\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Zak\Lists\Contracts\ComponentLoaderContract;

/**
 * Сохраняет пользовательские настройки таблицы: видимые колонки, сортировка, фильтры.
 */
class SaveOptionsAction
{
    public function __construct(
        private readonly ComponentLoaderContract $loader,
    ) {}

    public function handle(Request $request, string $list): RedirectResponse
    {
        $component = $this->loader->resolve($list);

        $data = $request->validate([
            'columns' => ['nullable', 'array'],
            'sort' => ['nullable', 'array'],
            'filters' => ['nullable', 'array'],
        ]);

        $options = $component->options->value;
        $options['columns'] = array_keys($data['columns'] ?? []);
        $options['filters'] = array_keys($data['filters'] ?? []);
        $options['sort'] = $data['sort'] ?? [];

        $component->options->value = $options;
        $component->options->save();

        return back()->with('js_success', __('lists.messages.options_saved'));
    }
}
