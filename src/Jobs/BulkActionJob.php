<?php

namespace Zak\Lists\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Zak\Lists\BulkAction;
use Zak\Lists\Contracts\ComponentLoaderContract;

/**
 * Выполняет bulk-действие асинхронно в очереди.
 *
 * Callback BulkAction должен быть invokable-классом (не замыканием),
 * так как замыкания не поддаются сериализации.
 */
class BulkActionJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $list  Имя файла компонента (без расширения)
     * @param  string  $actionKey  Ключ bulk-действия
     * @param  array<int, int>  $ids  Список ID выбранных элементов
     * @param  int  $userId  ID аутентифицированного пользователя
     */
    public function __construct(
        public readonly string $list,
        public readonly string $actionKey,
        public readonly array $ids,
        public readonly int $userId,
    ) {}

    public function handle(ComponentLoaderContract $loader): void
    {
        $user = $this->resolveUser();

        if (! $user) {
            info('Zak.Lists.BulkActionJob: пользователь #'.$this->userId.' не найден, задача пропущена.');

            return;
        }

        Auth::login($user);

        $component = $loader->resolve($this->list);

        /** @var BulkAction|null $action */
        $action = collect($component->bulkActions)
            ->first(fn ($a) => $a instanceof BulkAction && $a->key === $this->actionKey);

        if (! $action) {
            info('Zak.Lists.BulkActionJob: действие "'.$this->actionKey.'" не найдено в компоненте "'.$this->list.'".');

            return;
        }

        if (! is_object($action->callback) || ! method_exists($action->callback, '__invoke')) {
            info('Zak.Lists.BulkActionJob: callback для "'.$this->actionKey.'" не является invokable-классом. Пропущено.');

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $this->ids)));
        $chunkSize = max(1, (int) config('lists.bulk_chunk_size', 500));
        $items = new Collection;

        foreach (array_chunk($ids, $chunkSize) as $chunk) {
            $items = $items->merge(
                $component->getQuery()->whereIn('id', $chunk)->get()
            );
        }

        try {
            call_user_func($action->callback, $items, $component);
        } catch (Throwable $e) {
            if (isReportable($e)) {
                report('Zak.Lists.BulkActionJob: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine());
            }

            throw $e;
        }
    }

    private function resolveUser(): mixed
    {
        $provider = config('auth.providers.'.config('auth.guards.web.provider', 'users').'.model');

        if (! $provider || ! class_exists($provider)) {
            return null;
        }

        return $provider::find($this->userId);
    }
}
