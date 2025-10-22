<?php

namespace Zak\Lists;

class BulkAction
{
    public string $confirmText = "Вы уверены? Это действие нельзя будет отменить.";
    public string $successMessage = "Действие выполнено успешно.";

    public function __construct(
        public string $name,
        public string $key,
        public \Closure $callback,
    ) {

    }

    public function label()
    {
        return $this->name;
    }

    public function key()
    {
        return $this->key;
    }

    public function confirmText(): string
    {
        return $this->confirmText;
    }

    public function getSuccessMessage(): string
    {
        return $this->successMessage;
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


}
