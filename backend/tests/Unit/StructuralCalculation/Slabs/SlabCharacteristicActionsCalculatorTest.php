<?php

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsCalculator;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsException;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsRejectionReason;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;

function slabCharacteristicActionsCalculator(): SlabCharacteristicActionsCalculator
{
    return app(SlabCharacteristicActionsCalculator::class);
}

it('calculates characteristic surface actions from the independent reference case', function () {
    $result = slabCharacteristicActionsCalculator()->calculate(
        new SlabGeometry(5000, 200),
        new SlabSurfaceLoads(1.5, 1.0, 0.5, 2.0),
    );

    expect($result->thicknessMillimetres)->toBe(200.0)
        ->and($result->thicknessMetres)->toBe(0.2)
        ->and($result->unitWeight->value)->toBe(25.0)
        ->and($result->unitWeight::UNIT)->toBe('kN/m³')
        ->and($result->selfWeight)->toBe(5.0)
        ->and($result->finishes)->toBe(1.5)
        ->and($result->partitions)->toBe(1.0)
        ->and($result->otherPermanent)->toBe(0.5)
        ->and($result->permanentTotal)->toBe(8.0)
        ->and($result->imposedLoad)->toBe(2.0)
        ->and($result::LOAD_UNIT)->toBe('kN/m²')
        ->and($result::SELF_WEIGHT_FORMULA)->toBe('gk_self = γ_concrete × h')
        ->and($result::PERMANENT_TOTAL_FORMULA)->toBe('Gk_total = gk_self + gk_finishes + gk_partitions + gk_otherPermanent');
});

it('keeps the permanent total equal to self weight when optional permanent loads are zero', function () {
    $result = slabCharacteristicActionsCalculator()->calculate(
        new SlabGeometry(5000, 160),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );

    expect($result->selfWeight)->toBe(4.0)
        ->and($result->permanentTotal)->toBe(4.0)
        ->and($result->imposedLoad)->toBe(0.0);
});

it('does not use the calculation strip width for a surface load', function () {
    $result = slabCharacteristicActionsCalculator()->calculate(
        new SlabGeometry(5000, 200),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );

    expect($result->selfWeight)->toBe(5.0)
        ->and($result->permanentTotal)->toBe(5.0)
        ->and(property_exists($result, 'calculationStripWidth'))->toBeFalse()
        ->and(property_exists($result, 'wEd'))->toBeFalse()
        ->and(property_exists($result, 'MEd'))->toBeFalse()
        ->and(property_exists($result, 'VEd'))->toBeFalse();
});

it('protects calculator invariants', function (SlabGeometry $geometry, SlabSurfaceLoads $loads, ReinforcedConcreteUnitWeight $unitWeight, SlabCharacteristicActionsRejectionReason $reason) {
    try {
        slabCharacteristicActionsCalculator()->calculateWithUnitWeight($geometry, $loads, $unitWeight);
    } catch (SlabCharacteristicActionsException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid slab characteristic actions to be rejected.');
})->with([
    'invalid thickness' => [new SlabGeometry(5000, 0), new SlabSurfaceLoads(0, 0, 0, 0), new ReinforcedConcreteUnitWeight(25), SlabCharacteristicActionsRejectionReason::INVALID_THICKNESS],
    'invalid unit weight' => [new SlabGeometry(5000, 200), new SlabSurfaceLoads(0, 0, 0, 0), new ReinforcedConcreteUnitWeight(0), SlabCharacteristicActionsRejectionReason::INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT],
    'negative finishes' => [new SlabGeometry(5000, 200), new SlabSurfaceLoads(-1, 0, 0, 0), new ReinforcedConcreteUnitWeight(25), SlabCharacteristicActionsRejectionReason::INVALID_FINISHES],
    'negative partitions' => [new SlabGeometry(5000, 200), new SlabSurfaceLoads(0, -1, 0, 0), new ReinforcedConcreteUnitWeight(25), SlabCharacteristicActionsRejectionReason::INVALID_PARTITIONS],
    'negative other permanent' => [new SlabGeometry(5000, 200), new SlabSurfaceLoads(0, 0, -1, 0), new ReinforcedConcreteUnitWeight(25), SlabCharacteristicActionsRejectionReason::INVALID_OTHER_PERMANENT],
    'negative imposed load' => [new SlabGeometry(5000, 200), new SlabSurfaceLoads(0, 0, 0, -1), new ReinforcedConcreteUnitWeight(25), SlabCharacteristicActionsRejectionReason::INVALID_IMPOSED_LOAD],
]);
