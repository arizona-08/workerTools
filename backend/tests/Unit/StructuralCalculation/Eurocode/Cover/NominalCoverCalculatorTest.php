<?php

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationException;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationRejectionReason;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationScope;
use App\StructuralCalculation\Eurocode\Cover\CoverGoverningCriterion;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Cover\StructuralClass;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\Exposure\ExposureClassRepository;

function calculateNominalCover(CoverCalculationInput $input)
{
    return app(NominalCoverCalculator::class)->calculate($input, app(FrenchEurocodeProfileRepository::class)->get());
}

function automaticCoverInput(array $overrides = []): CoverCalculationInput
{
    return new CoverCalculationInput(
        coverMode: CoverMode::AUTO,
        exposureClasses: $overrides['exposureClasses'] ?? [ExposureClassCode::XC4],
        concreteClass: array_key_exists('concreteClass', $overrides) ? $overrides['concreteClass'] : ConcreteStrengthClass::C25_30,
        designWorkingLifeYears: $overrides['designWorkingLifeYears'] ?? 50,
        reinforcementDiameter: $overrides['reinforcementDiameter'] ?? 16.0,
        compactCover: $overrides['compactCover'] ?? false,
        scope: $overrides['scope'] ?? new CoverCalculationScope,
    );
}

it('calculates an independently checked nominal cover for the standard MVP case', function () {
    $result = calculateNominalCover(automaticCoverInput());

    // Reference: S4 + XC4 -> c_min,dur 30 mm; max(16, 30, 10) + 10 = 40 mm.
    expect($result->cMinDurability)->toBe(30.0)
        ->and($result->cMin)->toBe(30.0)
        ->and($result->cNom)->toBe(40.0)
        ->and($result->governingCriterion)->toBe(CoverGoverningCriterion::DURABILITY)
        ->and($result->unit)->toBe('mm');
});

it('identifies bond as governing when the individual bar diameter is larger', function () {
    $result = calculateNominalCover(automaticCoverInput([
        'exposureClasses' => [ExposureClassCode::XC1],
        'reinforcementDiameter' => 50.0,
    ]));

    expect($result->cMinBond)->toBe(50.0)
        ->and($result->cMin)->toBe(50.0)
        ->and($result->cNom)->toBe(60.0)
        ->and($result->governingCriterion)->toBe(CoverGoverningCriterion::BOND);
});

it('identifies the absolute 10 mm requirement as governing', function () {
    $result = calculateNominalCover(automaticCoverInput([
        'exposureClasses' => [ExposureClassCode::XC1],
        'concreteClass' => ConcreteStrengthClass::C30_37,
        'reinforcementDiameter' => 6.0,
    ]));

    expect($result->cMinDurability)->toBe(10.0)
        ->and($result->cMin)->toBe(10.0)
        ->and($result->cNom)->toBe(20.0)
        ->and($result->governingCriterion)->toBe(CoverGoverningCriterion::MINIMUM_10_MM);
});

it('retains every exposure result and selects the most severe durability requirement', function () {
    $result = calculateNominalCover(automaticCoverInput([
        'exposureClasses' => [ExposureClassCode::XC4, ExposureClassCode::XD2],
    ]));

    expect($result->governingExposureClass)->toBe(ExposureClassCode::XD2)
        ->and($result->cMinDurability)->toBe(40.0)
        ->and(array_map(fn ($entry) => $entry->exposureClass, $result->exposureResults))
        ->toBe([ExposureClassCode::XD2, ExposureClassCode::XC4]);
});

it('traces the concrete strength structural-class modulation', function () {
    $result = calculateNominalCover(automaticCoverInput([
        'exposureClasses' => [ExposureClassCode::XC3],
        'concreteClass' => ConcreteStrengthClass::C30_37,
    ]));

    expect($result->initialStructuralClass)->toBe(StructuralClass::S4)
        ->and($result->finalStructuralClass)->toBe(StructuralClass::S3)
        ->and($result->structuralClassModifiers[1]->rule)->toBe('concreteStrength')
        ->and($result->structuralClassModifiers[1]->value)->toBe(-1)
        ->and($result->cMinDurability)->toBe(20.0);
});

it('uses the profile default execution deviation and represents all durability adjustments', function () {
    $result = calculateNominalCover(automaticCoverInput());

    expect($result->deltaCDurGamma)->toBe(0.0)
        ->and($result->deltaCDurSt)->toBe(0.0)
        ->and($result->deltaCDurAdd)->toBe(0.0)
        ->and($result->deltaCDev)->toBe(10.0);
});

it('returns a manual cover with an explicit non-compliance warning', function () {
    $result = calculateNominalCover(new CoverCalculationInput(
        coverMode: CoverMode::MANUAL,
        manualNominalCover: 35.0,
    ));

    expect($result->coverMode)->toBe(CoverMode::MANUAL)
        ->and($result->cNom)->toBe(35.0)
        ->and($result->warnings[0])->toContain('n’est pas vérifiée');
});

it('rejects invalid automatic input and unsupported configurations', function (CoverCalculationInput $input, CoverCalculationRejectionReason $reason) {
    try {
        calculateNominalCover($input);
    } catch (CoverCalculationException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected cover calculation to be rejected.');
})->with([
    'missing exposure' => [automaticCoverInput(['exposureClasses' => []]), CoverCalculationRejectionReason::NO_EXPOSURE_CLASS],
    'invalid diameter' => [automaticCoverInput(['reinforcementDiameter' => 0.0]), CoverCalculationRejectionReason::INVALID_REINFORCEMENT_DIAMETER],
    'missing concrete class' => [automaticCoverInput(['concreteClass' => null]), CoverCalculationRejectionReason::STRUCTURAL_CLASS_RULE_NOT_SUPPORTED],
    'invalid working life' => [automaticCoverInput(['designWorkingLifeYears' => 0]), CoverCalculationRejectionReason::INVALID_DESIGN_WORKING_LIFE],
    'unsupported working life rule' => [automaticCoverInput(['designWorkingLifeYears' => 75]), CoverCalculationRejectionReason::STRUCTURAL_CLASS_RULE_NOT_SUPPORTED],
    'freeze thaw without reference class' => [automaticCoverInput(['exposureClasses' => [ExposureClassCode::XF1]]), CoverCalculationRejectionReason::UNSUPPORTED_EXPOSURE_CLASS],
    'out of MVP scope' => [automaticCoverInput(['scope' => new CoverCalculationScope(passiveReinforcement: false)]), CoverCalculationRejectionReason::UNSUPPORTED_CONFIGURATION],
]);

it('rejects a non-positive manually imposed nominal cover', function () {
    calculateNominalCover(new CoverCalculationInput(
        coverMode: CoverMode::MANUAL,
        manualNominalCover: 0.0,
    ));
})->throws(CoverCalculationException::class, CoverCalculationRejectionReason::INVALID_MANUAL_NOMINAL_COVER->value);

it('does not resolve an invalid external exposure identifier before typed cover calculation', function () {
    expect(app(ExposureClassRepository::class)->find('XZ1'))->toBeNull();
});

it('keeps national cover parameters in the profile requirements, not in the calculators', function () {
    $requirements = app(FrenchEurocodeProfileRepository::class)->get()->coverRequirements;

    expect($requirements->defaultDeltaCDev)->toBe(10.0)
        ->and($requirements->minimumAbsoluteCover)->toBe(10.0)
        ->and($requirements->minimumDurabilityCoverFor(StructuralClass::S4, ExposureClassCode::XC4))->toBe(30.0);
});
