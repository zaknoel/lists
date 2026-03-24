<?php

namespace Zak\Lists;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\FieldCollection;
use Zak\Lists\Models\UserOption;

class Component
{
    public UserOption $options;

    public string $grid_id = '';

    private string $className;

    public function __construct(
        protected string $model,
        protected string $label,
        protected string $singleLabel,
        protected array $fields = [],
        protected ?array $actions = null,
        protected ?array $pages = null,
        protected string $customScript = '',

        protected ?Closure $OnQuery = null,
        protected ?Closure $OnIndexQuery = null,
        protected ?Closure $OnDetailQuery = null,
        protected ?Closure $OnEditQuery = null,

        protected ?Closure $OnBeforeSave = null,
        protected ?Closure $OnAfterSave = null,
        protected ?Closure $OnBeforeDelete = null,
        protected ?Closure $OnAfterDelete = null,
        protected ?Closure $canView = null,
        protected ?Closure $canViewAny = null,
        protected ?Closure $canAdd = null,
        protected ?Closure $canEdit = null,
        protected ?Closure $canDelete = null,
        public string $customButtons = '',
        public ?Closure $callCustomDetailButtons = null,
        public bool $bShowEditButtonOnDetail = true,
        public array $customViews = [],
        public ?Closure $customAddPage = null,
        public ?Closure $customEditPage = null,
        public ?Closure $customDetailPage = null,
        public ?Closure $customDeletePage = null,
        public array $customLabels = [],
        public ?array $bulkActions = null,
    ) {
        if (! $this->model) {
            throw new InvalidArgumentException('Model not set!');
        }

        $this->className = class_basename($this->model);

        // Дефолтные замыкания прав — используют политики Laravel
        $user = auth()->user();

        if ($user) {
            $this->canAdd = $this->canAdd ?? fn () => $user->can('add', $this->model);
            $this->canEdit = $this->canEdit ?? static fn ($item) => $user->can('edit', $item);
            $this->canDelete = $this->canDelete ?? static fn ($item) => $user->can('delete', $item);
            $this->canView = $this->canView ?? static fn ($item) => $user->can('view', $item);
            $this->canViewAny = $this->canViewAny ?? fn () => $user->can('viewAny', $this->model);
        } else {
            // Без аутентифицированного пользователя — запрещаем всё
            $this->canAdd = $this->canAdd ?? fn () => false;
            $this->canEdit = $this->canEdit ?? static fn ($item) => false;
            $this->canDelete = $this->canDelete ?? static fn ($item) => false;
            $this->canView = $this->canView ?? static fn ($item) => false;
            $this->canViewAny = $this->canViewAny ?? fn () => false;
        }

        if ($this->actions === null) {
            $this->actions = array_filter([
                Action::make(__('lists.actions.view'))->showAction()->default(),
                Action::make(__('lists.actions.edit'))->editAction(),
                Action::make(__('lists.actions.delete'))->deleteAction(),
            ]);
        }

        if ($this->bulkActions === null) {
            $this->bulkActions = [
                BulkAction::make(__('lists.actions.delete'), 'bulk-delete', static function (EloquentCollection $items, Component $component): void {
                    foreach ($items as $item) {
                        abort_unless($component->userCanDelete($item), 403, __('lists.errors.unauthorized'));
                        $component->eventOnBeforeDelete($item);
                        $item->deleteOrFail();
                        $component->eventOnAfterDelete($item);
                    }
                })->setSuccessMessage(__('lists.messages.bulk_deleted')),
            ];
        }

        $this->grid_id = $this->model;

        if ($user?->id) {
            $this->options = UserOption::firstOrCreate(
                ['user_id' => $user->id, 'name' => $this->grid_id],
                [
                    'user_id' => $user->id,
                    'name' => $this->grid_id,
                    'value' => ['columns' => [], 'sort' => [], 'filters' => [], 'curSort' => []],
                ]
            );
        } else {
            // Заглушка без обращения к БД — для тестов/CLI
            $this->options = new UserOption([
                'user_id' => 0,
                'name' => $this->grid_id,
                'value' => ['columns' => [], 'sort' => [], 'filters' => [], 'curSort' => []],
            ]);
        }

        $this->fields = array_values(array_filter(
            $this->fields,
            static fn ($field) => $field instanceof Field
        ));
    }

    /**
     * Генерирует файл политики для модели, если он ещё не существует.
     * Вызывается явно (например, из Artisan-команды), не из конструктора.
     */
    public function ensurePolicyExists(): void
    {
        $path = app_path('Policies/'.$this->className.'Policy.php');

        if (file_exists($path)) {
            return;
        }

        Artisan::call('make:policy', ['name' => $this->className.'Policy', '-m' => $this->model]);

        $content = file_get_contents($path);
        file_put_contents($path, str_replace('return false;', 'return true;', $content));
    }

    public function userCanViewAny(): bool
    {
        return (bool) (is_callable($this->canViewAny)
            ? call_user_func($this->canViewAny)
            : $this->canViewAny);
    }

    public function userCanView(mixed $item): bool
    {
        return (bool) (is_callable($this->canView)
            ? call_user_func($this->canView, $item)
            : $this->canView);
    }

    public function userCanAdd(): bool
    {
        return (bool) (is_callable($this->canAdd)
            ? call_user_func($this->canAdd)
            : $this->canAdd);
    }

    public function userCanEdit(mixed $item): bool
    {
        return (bool) (is_callable($this->canEdit)
            ? call_user_func($this->canEdit, $item)
            : $this->canEdit);
    }

    public function userCanDelete(mixed $item): bool
    {
        return (bool) (is_callable($this->canDelete)
            ? call_user_func($this->canDelete, $item)
            : $this->canDelete);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCustomLabel(string $key): ?string
    {
        return $this->customLabels[$key] ?? null;
    }

    public function getSingleLabel(): string
    {
        return str($this->singleLabel)->lower();
    }

    public function getActions(): array
    {
        return $this->actions ?? [];
    }

    public function getPages(): array
    {
        return $this->pages ?? [];
    }

    public function getCustomScript(): string
    {
        return $this->customScript;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Возвращает поля как типизированную коллекцию с удобными методами фильтрации.
     */
    public function fieldCollection(): FieldCollection
    {
        return FieldCollection::fromArray($this->fields);
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFilteredFields(Closure $callback): array
    {
        return array_values(array_filter($this->fields, $callback));
    }

    public function getQuery(): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->model;

        return $modelClass::query();
    }

    public function getFilteredActions(mixed $item): array
    {
        return array_values(array_filter(
            $this->actions ?? [],
            fn (Action $action) => $action->isShown($this, $item)
        ));
    }

    /**
     * Определяет номер начальной колонки данных (со смещением на action/checkbox колонки).
     */
    public function getSortInt(): int
    {
        $int = 0;

        if ($this->getActions()) {
            $int++;
        }

        if ($this->bulkActions) {
            $int++;
        }

        return $int;
    }

    // ── Events ────────────────────────────────────────────────────────────────

    public function eventOnQuery(mixed $query): mixed
    {
        if ($this->OnQuery && is_callable($this->OnQuery)) {
            return call_user_func($this->OnQuery, $query);
        }

        return $query;
    }

    public function eventOnIndexQuery(mixed $query): mixed
    {
        $this->eventOnQuery($query);

        if ($this->OnIndexQuery && is_callable($this->OnIndexQuery)) {
            return call_user_func($this->OnIndexQuery, $query);
        }

        return $query;
    }

    public function eventOnDetailQuery(mixed $query): mixed
    {
        $this->eventOnQuery($query);

        if ($this->OnDetailQuery && is_callable($this->OnDetailQuery)) {
            return call_user_func($this->OnDetailQuery, $query);
        }

        return $query;
    }

    public function eventOnEditQuery(mixed $query): mixed
    {
        $this->eventOnQuery($query);

        if ($this->OnEditQuery && is_callable($this->OnEditQuery)) {
            return call_user_func($this->OnEditQuery, $query);
        }

        return $query;
    }

    public function eventOnBeforeSave(mixed $item): mixed
    {
        if ($this->OnBeforeSave && is_callable($this->OnBeforeSave)) {
            return call_user_func($this->OnBeforeSave, $item);
        }

        return $item;
    }

    public function eventOnAfterSave(mixed $item): mixed
    {
        if ($this->OnAfterSave && is_callable($this->OnAfterSave)) {
            return call_user_func($this->OnAfterSave, $item);
        }

        return $item;
    }

    public function eventOnBeforeDelete(mixed $item): mixed
    {
        if ($this->OnBeforeDelete && is_callable($this->OnBeforeDelete)) {
            return call_user_func($this->OnBeforeDelete, $item);
        }

        return $item;
    }

    public function eventOnAfterDelete(mixed $item): mixed
    {
        if ($this->OnAfterDelete && is_callable($this->OnAfterDelete)) {
            return call_user_func($this->OnAfterDelete, $item);
        }

        return $item;
    }

    // ── Routing ───────────────────────────────────────────────────────────────

    public function checkCustomPath(string $property, mixed $context = null): void
    {
        if ($this->{$property} && is_callable($this->{$property})) {
            $redirectTo = call_user_func($this->{$property}, $context);

            if ($redirectTo) {
                throw new HttpResponseException(redirect()->to($redirectTo));
            }
        }
    }

    public function getRoute(string $route, string $list, mixed $context = null): string
    {
        $customProperties = [
            'lists_add' => 'customAddPage',
            'add_save' => 'customAddPage',
            'lists_edit' => 'customEditPage',
            'edit_save' => 'customEditPage',
            'lists_detail' => 'customDetailPage',
            'lists_delete' => 'customDeletePage',
        ];

        $property = $customProperties[$route] ?? $route;

        if (isset($this->{$property}) && is_callable($this->{$property})) {
            $redirectTo = call_user_func($this->{$property}, $context);

            if ($redirectTo) {
                return $redirectTo;
            }
        }

        return route($route, [
            'list' => $list,
            'item' => $context?->id,
        ]);
    }

    // ── Scripts ───────────────────────────────────────────────────────────────

    /**
     * Возвращает HTML-скрипты, нужные для полей текущего компонента.
     */
    public function scripts(): string
    {
        $yandexMapsKey = (string) config('lists.yandex_maps_key', '');
        $locationScript = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';

        if ($yandexMapsKey !== '') {
            $locationScript .= '&apikey='.urlencode($yandexMapsKey);
        }

        $scripts = [
            'location' => [
                '<script src="'.$locationScript.'" type="text/javascript"></script>',
            ],
            'checkbox' => [
                '<link rel="stylesheet" href="'.asset('vendor/lists/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css').'">',
                '<script src="'.asset('vendor/lists/bootstrap-switch/dist/js/bootstrap-switch.min.js').'"></script>',
            ],
        ];

        $result = [];

        foreach ($scripts as $key => $tags) {
            if (Arr::where($this->fields, fn (Field $f) => $f->componentName() === $key)) {
                $result[] = implode(PHP_EOL, $tags);
            }
        }

        $result[] = $this->customScript;

        return implode(PHP_EOL, $result);
    }

    // ── Private helpers ───────────────────────────────────────────────────────
}
