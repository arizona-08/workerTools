<?php

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\SteelDuctilityClass;

it('returns the intrinsic material properties of B500B', function () {
    $properties = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);

    expect($properties->grade)->toBe(ReinforcementSteelGrade::B500B)
        ->and($properties->fyk)->toBe(500.0)
        ->and($properties->es)->toBe(200000.0)
        ->and($properties->ductilityClass)->toBe(SteelDuctilityClass::B);
});

it('resolves B500B from its external identifier', function () {
    $properties = app(ReinforcementSteelGradeRepository::class)->find('B500B');

    expect($properties)->not->toBeNull()
        ->and($properties->fyk)->toBe(500.0)
        ->and($properties->es)->toBe(200000.0);
});

it('does not resolve an unsupported reinforcement steel grade', function () {
    $properties = app(ReinforcementSteelGradeRepository::class)->find('B600C');

    expect($properties)->toBeNull();
});

it('does not expose profile-dependent design properties as material properties', function () {
    $properties = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);

    expect(property_exists($properties, 'gammaS'))->toBeFalse()
        ->and(property_exists($properties, 'fyd'))->toBeFalse();
});
