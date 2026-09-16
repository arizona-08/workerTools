<?php

use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteDesignStrengthCalculator;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSteelDesignStrengthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

it('provides the versioned French profile and its material safety factors', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();

    expect($profile->identifier)->toBe(DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR)
        ->and($profile->fundamentalUltimateCombinationExpression)->toBe(FundamentalUltimateCombinationExpression::EN1990_6_10)
        ->and($profile->materialSafetyFactors->gammaC)->toBe(1.5)
        ->and($profile->materialSafetyFactors->gammaS)->toBe(1.15)
        ->and($profile->materialSafetyFactors->alphaCc)->toBe(1.0)
        ->and($profile->beamLongitudinalReinforcementRequirements->minimumReinforcementStrengthCoefficient)->toBe(0.26)
        ->and($profile->beamLongitudinalReinforcementRequirements->minimumReinforcementRatio)->toBe(0.0013)
        ->and($profile->beamConcreteShearResistanceRequirements->concreteShearResistanceCoefficient)->toBe(0.12)
        ->and($profile->beamConcreteShearResistanceRequirements->compressionStressCoefficient)->toBe(0.15)
        ->and($profile->beamConcreteShearResistanceRequirements->minimumShearStressCoefficient)->toBe(0.035)
        ->and($profile->beamConcreteShearResistanceRequirements->minimumCotTheta)->toBe(1.0)
        ->and($profile->beamConcreteShearResistanceRequirements->maximumCotTheta)->toBe(2.5)
        ->and($profile->beamConcreteShearResistanceRequirements->minimumShearReinforcementCoefficient)->toBe(0.08)
        ->and($profile->beamConcreteShearResistanceRequirements->concreteShearStrengthReductionCoefficient)->toBe(0.6)
        ->and($profile->beamConcreteShearResistanceRequirements->concreteShearStrengthReductionReferenceStrength)->toBe(250.0)
        ->and($profile->beamConcreteShearResistanceRequirements->nonPrestressedAlphaCw)->toBe(1.0)
        ->and($profile->beamCrackWidthRequirements->crackBondCoefficient)->toBe(0.8)
        ->and($profile->beamCrackWidthRequirements->crackStrainDistributionCoefficient)->toBe(0.5)
        ->and($profile->beamCrackWidthRequirements->crackSpacingCoefficient3)->toBe(3.4)
        ->and($profile->beamCrackWidthRequirements->crackSpacingCoefficient4)->toBe(0.425)
        ->and($profile->beamCrackWidthRequirements->longTermKt)->toBe(0.4)
        ->and($profile->beamDeflectionRequirements->baseRatioConstant)->toBe(11.0)
        ->and($profile->beamDeflectionRequirements->structuralFactorFor(BeamSupportSystem::CANTILEVER))->toBe(0.4)
        ->and($profile->beamDeflectionRequirements->referenceReinforcementRatioFactor)->toBe(0.001)
        ->and($profile->beamDeflectionRequirements->referenceSteelStrength)->toBe(500.0)
        ->and($profile->beamServiceStressRequirements->concreteCharacteristicStressLimitFactor)->toBe(0.60)
        ->and($profile->beamServiceStressRequirements->concreteQuasiPermanentStressLimitFactor)->toBe(0.45)
        ->and($profile->beamServiceStressRequirements->reinforcementCharacteristicStressLimitFactor)->toBe(0.80)
        ->and($profile->reinforcementSpacingRequirements->barDiameterFactor)->toBe(1.0)
        ->and($profile->reinforcementSpacingRequirements->aggregateSizeAllowance)->toBe(5.0);
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
