<?php

use App\StructuralCalculation\Eurocode\Concrete\ConcreteDesignStrengthCalculator;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSteelDesignStrengthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

it('provides the versioned French profile and its material safety factors', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();

    expect($profile->identifier)->toBe(DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR)
        ->and($profile->materialSafetyFactors->gammaC)->toBe(1.5)
        ->and($profile->materialSafetyFactors->gammaS)->toBe(1.15)
        ->and($profile->materialSafetyFactors->alphaCc)->toBe(1.0);
});

it('provides action and category-specific combination factors', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $combinationFactors = $profile->combinationFactorsFor(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS);

    expect($profile->actionSafetyFactors->gammaGUnfavourable)->toBe(1.35)
        ->and($profile->actionSafetyFactors->gammaGFavourable)->toBe(1.0)
        ->and($profile->actionSafetyFactors->gammaQ)->toBe(1.5)
        ->and($combinationFactors)->not->toBeNull()
        ->and($combinationFactors->psi0)->toBe(0.7)
        ->and($combinationFactors->psi1)->toBe(0.5)
        ->and($combinationFactors->psi2)->toBe(0.3);
});

it('does not resolve an unknown design code profile', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->find('EN_1992_1_1_OTHER_NA');

    expect($profile)->toBeNull();
});

it('derives fcd from the concrete material and the selected profile', function () {
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();

    expect(app(ConcreteDesignStrengthCalculator::class)->calculate($concrete, $profile))->toBe(20.0);
});

it('derives fyd from B500B and the selected profile', function () {
    $steel = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();

    expect(round(app(ReinforcementSteelDesignStrengthCalculator::class)->calculate($steel, $profile), 6))
        ->toBe(434.782609);
});

it('keeps profile parameters out of material property objects', function () {
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C25_30);
    $steel = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);

    expect(property_exists($concrete, 'gammaC'))->toBeFalse()
        ->and(property_exists($concrete, 'alphaCc'))->toBeFalse()
        ->and(property_exists($steel, 'gammaS'))->toBeFalse()
        ->and(property_exists($steel, 'fyd'))->toBeFalse();
});
