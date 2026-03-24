<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Zak\Lists\Fields\Field;
use Zak\Lists\Fields\FieldCollection;

// ── Debug helpers ─────────────────────────────────────────────────────────────

it('will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

// ── Jobs ──────────────────────────────────────────────────────────────────────

it('all Jobs implement ShouldQueue')
    ->expect('Zak\Lists\Jobs')
    ->toImplement(ShouldQueue::class);

// ── Contracts ─────────────────────────────────────────────────────────────────

it('all Contracts are interfaces')
    ->expect('Zak\Lists\Contracts')
    ->toBeInterfaces();

// ── Fields ────────────────────────────────────────────────────────────────────

it('all Fields extend the base Field class')
    ->expect('Zak\Lists\Fields')
    ->classes()
    ->toExtend(Field::class)
    ->ignoring([
        Field::class,
        FieldCollection::class,
        'Zak\Lists\Fields\Casts',
        'Zak\Lists\Fields\Contracts',
        'Zak\Lists\Fields\Traits',
    ]);

// ── Actions ───────────────────────────────────────────────────────────────────

it('legacy ListComponent class is removed')
    ->expect(class_exists('Zak\Lists\ListComponent'))
    ->toBeFalse();
