<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Zak\Lists\Action;
use Zak\Lists\BulkAction;
use Zak\Lists\Component;
use Zak\Lists\Fields\FieldCollection;
use Zak\Lists\Fields\Location;
use Zak\Lists\Fields\Text;
use Zak\Lists\Tests\Fixtures\Models\TestUser;

beforeEach(function () {
    $this->user = TestUser::factory()->create();
    Auth::login($this->user);
});

// ── Конструктор ───────────────────────────────────────────────────────────────

it('создаётся с минимальными параметрами', function () {
    $component = new Component(
        model: TestUser::class,
        label: 'Пользователи',
        singleLabel: 'пользователь',
    );

    expect($component->getModel())->toBe(TestUser::class);
    expect($component->getLabel())->toBe('Пользователи');
    expect($component->getSingleLabel())->toBe('пользователь');
});

it('выбрасывает исключение если model не задана', function () {
    new Component(model: '', label: 'Test', singleLabel: 'test');
})->throws(InvalidArgumentException::class);

it('дефолтные actions создаются когда actions=null', function () {
    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        canView: fn ($i) => true,
        canEdit: fn ($i) => true,
        canDelete: fn ($i) => true,
        canViewAny: fn () => true,
        canAdd: fn () => true,
    );

    expect($component->getActions())->toHaveCount(3);
});

it('дефолтные actions не создаются когда actions=[] пустой массив', function () {
    $component = new Component(
        model: TestUser::class,
        label: 'Test',
        singleLabel: 'test',
        actions: [],
    );

    expect($component->getActions())->toHaveCount(0);
});

// ── Права доступа ─────────────────────────────────────────────────────────────

it('userCanViewAny() использует canViewAny closure', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canViewAny: fn () => true,
    );
    expect($component->userCanViewAny())->toBeTrue();

    $component2 = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canViewAny: fn () => false,
    );
    expect($component2->userCanViewAny())->toBeFalse();
});

it('userCanView() использует canView closure', function () {
    $item = TestUser::factory()->create();

    $allow = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canView: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );
    $deny = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canView: fn ($i) => false, canViewAny: fn () => true, canAdd: fn () => true,
        canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );

    expect($allow->userCanView($item))->toBeTrue();
    expect($deny->userCanView($item))->toBeFalse();
});

it('userCanAdd() использует canAdd closure', function () {
    $allow = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canAdd: fn () => true, canViewAny: fn () => true,
        canView: fn ($i) => true, canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );
    $deny = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canAdd: fn () => false, canViewAny: fn () => true,
        canView: fn ($i) => true, canEdit: fn ($i) => true, canDelete: fn ($i) => true,
    );

    expect($allow->userCanAdd())->toBeTrue();
    expect($deny->userCanAdd())->toBeFalse();
});

it('userCanEdit() использует canEdit closure', function () {
    $item = TestUser::factory()->create();

    $allow = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canEdit: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canView: fn ($i) => true, canDelete: fn ($i) => true,
    );
    $deny = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canEdit: fn ($i) => false, canViewAny: fn () => true, canAdd: fn () => true,
        canView: fn ($i) => true, canDelete: fn ($i) => true,
    );

    expect($allow->userCanEdit($item))->toBeTrue();
    expect($deny->userCanEdit($item))->toBeFalse();
});

it('userCanDelete() использует canDelete closure', function () {
    $item = TestUser::factory()->create();

    $allow = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canDelete: fn ($i) => true, canViewAny: fn () => true, canAdd: fn () => true,
        canView: fn ($i) => true, canEdit: fn ($i) => true,
    );
    $deny = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        canDelete: fn ($i) => false, canViewAny: fn () => true, canAdd: fn () => true,
        canView: fn ($i) => true, canEdit: fn ($i) => true,
    );

    expect($allow->userCanDelete($item))->toBeTrue();
    expect($deny->userCanDelete($item))->toBeFalse();
});

// ── Поля ─────────────────────────────────────────────────────────────────────

it('getFields() возвращает только валидные Field объекты', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        fields: [Text::make('Имя', 'name'), 'не поле', null],
    );

    expect($component->getFields())->toHaveCount(1);
});

it('setFields() заменяет поля компонента', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        fields: [Text::make('Имя', 'name')],
    );

    $component->setFields([Text::make('Email', 'email')]);

    expect($component->getFields()[0]->attribute)->toBe('email');
});

it('getFilteredFields() фильтрует поля по колбэку', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        fields: [
            Text::make('Имя', 'name')->required(),
            Text::make('Email', 'email'),
        ],
    );

    $required = $component->getFilteredFields(fn ($f) => $f->required);

    expect($required)->toHaveCount(1);
    expect($required[0]->attribute)->toBe('name');
});

it('fieldCollection() возвращает FieldCollection', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        fields: [Text::make('Имя', 'name')],
    );

    expect($component->fieldCollection())->toBeInstanceOf(FieldCollection::class);
});

// ── Действия ─────────────────────────────────────────────────────────────────

it('getFilteredActions() возвращает только видимые действия', function () {
    $item = TestUser::factory()->create();

    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        actions: [
            Action::make('Просмотр')->showAction(),
            Action::make('Редактировать')->editAction(),
        ],
        canView: fn ($i) => true,
        canEdit: fn ($i) => false,
        canViewAny: fn () => true,
        canAdd: fn () => true,
        canDelete: fn ($i) => true,
    );

    $filtered = $component->getFilteredActions($item);

    expect($filtered)->toHaveCount(1);
    expect($filtered[0]->action)->toBe('show');
});

// ── getSortInt() ──────────────────────────────────────────────────────────────

it('getSortInt() возвращает 0 без actions и bulkActions', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        actions: [], bulkActions: [],
    );

    expect($component->getSortInt())->toBe(0);
});

it('getSortInt() возвращает 1 с actions', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        actions: [Action::make('T')->showAction()], bulkActions: [],
    );

    expect($component->getSortInt())->toBe(1);
});

it('getSortInt() возвращает 2 с actions и bulkActions', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        actions: [Action::make('T')->showAction()],
        bulkActions: [BulkAction::make('T', 'k', fn ($i) => null)],
    );

    expect($component->getSortInt())->toBe(2);
});

// ── События ───────────────────────────────────────────────────────────────────

it('eventOnBeforeSave вызывает OnBeforeSave', function () {
    $called = false;
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        OnBeforeSave: function ($item) use (&$called) {
            $called = true;
        },
    );

    $item = TestUser::factory()->create();
    $component->eventOnBeforeSave($item);

    expect($called)->toBeTrue();
});

it('eventOnAfterSave вызывает OnAfterSave', function () {
    $called = false;
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        OnAfterSave: function ($item) use (&$called) {
            $called = true;
        },
    );

    $item = TestUser::factory()->create();
    $component->eventOnAfterSave($item);

    expect($called)->toBeTrue();
});

it('eventOnBeforeDelete вызывает OnBeforeDelete', function () {
    $called = false;
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        OnBeforeDelete: function ($item) use (&$called) {
            $called = true;
        },
    );

    $item = TestUser::factory()->create();
    $component->eventOnBeforeDelete($item);

    expect($called)->toBeTrue();
});

it('eventOnAfterDelete вызывает OnAfterDelete', function () {
    $called = false;
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        OnAfterDelete: function ($item) use (&$called) {
            $called = true;
        },
    );

    $item = TestUser::factory()->create();
    $component->eventOnAfterDelete($item);

    expect($called)->toBeTrue();
});

// ── Routing/Scripts ───────────────────────────────────────────────────────────

it('checkCustomPath выбрасывает HttpResponseException с redirect response', function () {
    $component = new Component(
        model: TestUser::class,
        label: 'T',
        singleLabel: 't',
        customAddPage: fn () => '/custom-add-page',
    );

    try {
        $component->checkCustomPath('customAddPage');
        $this->fail('Expected HttpResponseException was not thrown.');
    } catch (HttpResponseException $e) {
        expect($e->getResponse()->headers->get('Location'))->toBe('http://localhost/custom-add-page');
    }
});

it('scripts использует yandex key из конфига для location поля', function () {
    config()->set('lists.yandex_maps_key', 'test-key-123');

    $component = new Component(
        model: TestUser::class,
        label: 'T',
        singleLabel: 't',
        fields: [Location::make('Локация', 'location')],
    );

    $scripts = $component->scripts();

    expect($scripts)->toContain('api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=test-key-123');
});

it('scripts не добавляет apikey параметр если ключ не задан', function () {
    config()->set('lists.yandex_maps_key', null);

    $component = new Component(
        model: TestUser::class,
        label: 'T',
        singleLabel: 't',
        fields: [Location::make('Локация', 'location')],
    );

    $scripts = $component->scripts();

    expect($scripts)->toContain('api-maps.yandex.ru/2.1/?lang=ru_RU');
    expect($scripts)->not->toContain('&apikey=');
});

// ── Геттеры ───────────────────────────────────────────────────────────────────

it('getCustomLabel() возвращает custom label если задан', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
        customLabels: ['edit' => 'Моя редакция'],
    );

    expect($component->getCustomLabel('edit'))->toBe('Моя редакция');
    expect($component->getCustomLabel('missing'))->toBeNull();
});

it('getQuery() возвращает Builder для модели', function () {
    $component = new Component(
        model: TestUser::class, label: 'T', singleLabel: 't',
    );

    $query = $component->getQuery();

    expect($query->getModel())->toBeInstanceOf(TestUser::class);
});
