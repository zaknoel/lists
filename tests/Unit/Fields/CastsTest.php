<?php

use Carbon\Carbon;
use Zak\Lists\Fields\Casts\DateCast;
use Zak\Lists\Fields\Casts\IntegerCast;
use Zak\Lists\Fields\Casts\StringCast;

// ── StringCast ────────────────────────────────────────────────────────────────

describe('StringCast', function () {
    it('get() конвертирует значение в строку', function () {
        $cast = new StringCast;

        expect($cast->get(42))->toBe('42');
        expect($cast->get(null))->toBe('');
        expect($cast->get(true))->toBe('1');
    });

    it('get() возвращает строку без изменений', function () {
        $cast = new StringCast;

        expect($cast->get('hello'))->toBe('hello');
    });

    it('set() обрезает пробелы', function () {
        $cast = new StringCast;

        expect($cast->set('  hello  '))->toBe('hello');
    });

    it('set() конвертирует в строку и обрезает', function () {
        $cast = new StringCast;

        expect($cast->set(null))->toBe('');
        expect($cast->set(42))->toBe('42');
    });

    it('set() обрабатывает пустую строку', function () {
        $cast = new StringCast;

        expect($cast->set(''))->toBe('');
        expect($cast->set('   '))->toBe('');
    });
});

// ── IntegerCast ───────────────────────────────────────────────────────────────

describe('IntegerCast', function () {
    it('get() конвертирует в целое число', function () {
        $cast = new IntegerCast;

        expect($cast->get('42'))->toBe(42);
        expect($cast->get(3.7))->toBe(3);
    });

    it('get() возвращает 0 для null', function () {
        $cast = new IntegerCast;

        expect($cast->get(null))->toBe(0);
    });

    it('get() возвращает 0 для пустой строки', function () {
        $cast = new IntegerCast;

        expect($cast->get(''))->toBe(0);
    });

    it('get() возвращает int без изменений', function () {
        $cast = new IntegerCast;

        expect($cast->get(42))->toBe(42);
    });

    it('set() конвертирует в целое число', function () {
        $cast = new IntegerCast;

        expect($cast->set('100'))->toBe(100);
        expect($cast->set(null))->toBe(0);
    });
});

// ── DateCast ──────────────────────────────────────────────────────────────────

describe('DateCast', function () {
    it('get() конвертирует строку в Carbon', function () {
        $cast = new DateCast;

        $result = $cast->get('2025-06-15');

        expect($result)->toBeInstanceOf(Carbon::class);
        expect($result->format('Y-m-d'))->toBe('2025-06-15');
    });

    it('get() возвращает null для null', function () {
        $cast = new DateCast;

        expect($cast->get(null))->toBeNull();
    });

    it('get() возвращает null для пустой строки', function () {
        $cast = new DateCast;

        expect($cast->get(''))->toBeNull();
    });

    it('set() конвертирует Carbon в строку Y-m-d', function () {
        $cast = new DateCast;

        $result = $cast->set('2025-12-31');

        expect($result)->toBe('2025-12-31');
    });

    it('set() возвращает null для null', function () {
        $cast = new DateCast;

        expect($cast->set(null))->toBeNull();
    });

    it('set() возвращает null для пустой строки', function () {
        $cast = new DateCast;

        expect($cast->set(''))->toBeNull();
    });

    it('поддерживает кастомный формат через конструктор', function () {
        $cast = new DateCast('d/m/Y');

        $result = $cast->set('2025-06-15');

        expect($result)->toBe('15/06/2025');
    });

    it('get() принимает Carbon-объект', function () {
        $cast = new DateCast;
        $date = Carbon::parse('2025-01-20');

        $result = $cast->get($date);

        expect($result)->toBeInstanceOf(Carbon::class);
        expect($result->format('Y-m-d'))->toBe('2025-01-20');
    });
});
