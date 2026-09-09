<?php

use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;

it('returns the Eurocode material properties for C20/25', function () {
    $properties = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C20_25);

    expect($properties->strengthClass)->toBe(ConcreteStrengthClass::C20_25)
        ->and($properties->fck)->toBe(20.0)
        ->and($properties->fcm)->toBe(28.0)
        ->and($properties->fctm)->toBe(2.2)
        ->and($properties->ecm)->toBe(30000.0);
});

it('returns the properties attached to the requested concrete strength class', function () {
    $properties = app(ConcreteClassRepository::class)->find('C30/37');

    expect($properties)->not->toBeNull()
        ->and($properties->strengthClass)->toBe(ConcreteStrengthClass::C30_37)
        ->and($properties->fck)->toBe(30.0)
        ->and($properties->fcm)->toBe(38.0)
        ->and($properties->fctm)->toBe(2.9)
        ->and($properties->ecm)->toBe(33000.0);
});

it('lists the concrete classes available to future modules', function () {
    $classes = app(ConcreteClassRepository::class)->all();

    expect($classes)->toHaveCount(3)
        ->and(array_map(fn ($properties) => $properties->strengthClass->value, $classes))
        ->toBe(['C20/25', 'C25/30', 'C30/37']);
});

it('does not resolve an unsupported concrete class', function () {
    $properties = app(ConcreteClassRepository::class)->find('C90/105');

    expect($properties)->toBeNull();
});
