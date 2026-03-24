<?php

namespace Zak\Lists;

use Zak\Lists\Concerns\Makeable;

class BulkAction
{
    use Makeable;

    public string $confirmText = '';

    public string $successMessage = '';

    public string $icon = '';

    /**
     * Когда true — BulkActionRunner диспетчит задачу вместо синхронного выполнения.
     * Callback ДОЛЖЕН быть invokable-классом (не замыканием): замыкания нельзя сериализовать в очередь.
     */
    public bool $async = false;

    public function __construct(
        public string $name,
        public string $key,
        /** @var \Closure|object Closure для sync-действий; invokable-класс для async()-действий */
        public mixed $callback,
    ) {
        $this->confirmText = __('lists.messages.bulk_confirm');
        $this->successMessage = __('lists.messages.bulk_success');
    }

    public function setSuccessMessage(string $text): static
    {
        $this->successMessage = $text;

        return $this;
    }

    public function setConfirmText(string $text): static
    {
        $this->confirmText = $text;

        return $this;
    }

    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Помечает bulk action как асинхронный: callback выполнится в очереди.
     * Callback должен быть invokable-классом, иначе диспетч будет пропущен с предупреждением.
     */
    public function async(): static
    {
        $this->async = true;

        return $this;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage ?: __('lists.messages.bulk_success');
    }

    public function confirmText(): string
    {
        return $this->confirmText ?: __('lists.messages.bulk_confirm');
    }

    public function label(): string
    {
        return $this->name;
    }
}
