<?php

use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\SelfWeightCalculationException;
use App\StructuralCalculation\Beams\SelfWeightCalculationRejectionReason;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;

function selfWeightCalculator(): SelfWeightCalculator
{
    return app(SelfWeightCalculator::class);
}

function normalReinforcedConcreteUnitWeight(): ReinforcedConcreteUnitWeight
{
    return app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete();
}

it('calculates 4.50 kN/m from a 300 by 600 mm rectangular section', function () {
    $result = selfWeightCalculator()->calculate(new BeamGeometry(6500, 300, 600), true, normalReinforcedConcreteUnitWeight());

    expect($result->included)->toBeTrue()
        ->and($result->widthMillimetres)->toBe(300.0)
        ->and($result->heightMillimetres)->toBe(600.0)
        ->and($result->sectionArea)->toBe(0.18)
        ->and($result->unitWeight->value)->toBe(25.0)
        ->and($result->unitWeight::UNIT)->toBe('kN/m³')
        ->and($result->characteristicLineLoad)->toBe(4.5)
        ->and($result::SECTION_AREA_UNIT)->toBe('m²')
        ->and($result::LINE_LOAD_UNIT)->toBe('kN/m')
        ->and($result::FORMULA)->toBe('Gk_self = Ac × γ_RC');
});

it('calculates another rectangular section without premature rounding', function () {
    $result = selfWeightCalculator()->calculate(new BeamGeometry(4000, 200, 500), true, normalReinforcedConcreteUnitWeight());

    expect($result->sectionArea)->toBe(0.1)
        ->and($result->characteristicLineLoad)->toBe(2.5);
});

it('returns an explicit zero line load when self weight is excluded', function () {
    $result = selfWeightCalculator()->calculate(new BeamGeometry(6500, 300, 600), false, normalReinforcedConcreteUnitWeight());

    expect($result->included)->toBeFalse()
        ->and($result->sectionArea)->toBe(0.18)
        ->and($result->characteristicLineLoad)->toBe(0.0);
});

it('protects calculator invariants', function (BeamGeometry $geometry, ReinforcedConcreteUnitWeight $unitWeight, SelfWeightCalculationRejectionReason $reason) {
    try {
        selfWeightCalculator()->calculate($geometry, true, $unitWeight);
    } catch (SelfWeightCalculationException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid self-weight input to be rejected.');
})->with([
    'invalid width' => [new BeamGeometry(6500, 0, 600), new ReinforcedConcreteUnitWeight(25), SelfWeightCalculationRejectionReason::INVALID_WIDTH],
    'invalid height' => [new BeamGeometry(6500, 300, -1), new ReinforcedConcreteUnitWeight(25), SelfWeightCalculationRejectionReason::INVALID_HEIGHT],
    'invalid unit weight' => [new BeamGeometry(6500, 300, 600), new ReinforcedConcreteUnitWeight(0), SelfWeightCalculationRejectionReason::INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT],
]);
