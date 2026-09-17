<?php

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Slabs\SlabActionCombinations;
use App\StructuralCalculation\Slabs\SlabActionCombinationsCalculator;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsCalculator;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabStripLinearLoadsCalculator;
use App\StructuralCalculation\Slabs\SlabStripLinearLoadsException;
use App\StructuralCalculation\Slabs\SlabStripLinearLoadsRejectionReason;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;

function slabStripLinearLoadsCalculator(): SlabStripLinearLoadsCalculator
{
    return app(SlabStripLinearLoadsCalculator::class);
}

function slabReferenceCombinations()
{
    $actions = app(SlabCharacteristicActionsCalculator::class)->calculate(
        new SlabGeometry(5000, 200),
        new SlabSurfaceLoads(1.5, 1.0, 0.5, 2.0),
    );

    return app(SlabActionCombinationsCalculator::class)->calculate(SlabCalculationConfiguration::supported(), $actions);
}

it('converts all SLAB-05 surface combinations to the fixed one-metre strip', function () {
    $result = slabStripLinearLoadsCalculator()->calculate(new SlabGeometry(5000, 200), slabReferenceCombinations());

    expect($result->uls->stripWidthMillimetres)->toBe(1000.0)
        ->and($result->uls->stripWidthMetres)->toBe(1.0)
        ->and($result->uls->surfaceLoad)->toBe(13.8)
        ->and($result->uls->lineLoad)->toBe(13.8)
        ->and($result->slsCharacteristic->lineLoad)->toBe(10.0)
        ->and($result->slsFrequent->lineLoad)->toBe(9.0)
        ->and($result->slsQuasiPermanent->lineLoad)->toBe(8.6)
        ->and($result->uls::SURFACE_LOAD_UNIT)->toBe('kN/m²')
        ->and($result->uls::STRIP_WIDTH_UNIT)->toBe('m')
        ->and($result->uls::LINE_LOAD_UNIT)->toBe('kN/m')
        ->and($result->uls::FORMULA)->toBe('w = q × b')
        ->and(property_exists($result, 'MEd'))->toBeFalse()
        ->and(property_exists($result, 'VEd'))->toBeFalse();
});

it('consumes the given SLAB-05 combinations without recalculating them', function () {
    $source = new SlabSurfaceLoadCombination(
        SlabSurfaceLoadCombinationType::ULTIMATE,
        0,
        0,
        0,
        0,
        0,
        0,
        99.0,
        FundamentalUltimateCombinationExpression::EN1990_6_10,
        'provided by SLAB-05',
    );
    $combinations = new SlabActionCombinations($source, $source, $source, $source);

    expect(slabStripLinearLoadsCalculator()->calculate(new SlabGeometry(5000, 200), $combinations)->uls->lineLoad)->toBe(99.0);
});

it('rejects an invalid span before any static analysis is attempted', function () {
    slabStripLinearLoadsCalculator()->calculate(new SlabGeometry(0, 200), slabReferenceCombinations());
})->throws(SlabStripLinearLoadsException::class, SlabStripLinearLoadsRejectionReason::INVALID_EFFECTIVE_SPAN->value);

it('rejects a non-finite combined surface load', function () {
    $invalid = new SlabSurfaceLoadCombination(
        SlabSurfaceLoadCombinationType::ULTIMATE,
        0,
        0,
        0,
        0,
        0,
        0,
        INF,
        FundamentalUltimateCombinationExpression::EN1990_6_10,
        'invalid source',
    );

    slabStripLinearLoadsCalculator()->calculate(new SlabGeometry(5000, 200), new SlabActionCombinations($invalid, $invalid, $invalid, $invalid));
})->throws(SlabStripLinearLoadsException::class, SlabStripLinearLoadsRejectionReason::INVALID_ULTIMATE_SURFACE_LOAD->value);
