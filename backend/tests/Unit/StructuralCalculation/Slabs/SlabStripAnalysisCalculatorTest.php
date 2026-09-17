<?php

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Slabs\SlabActionCombinations;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabStripAnalysisCalculator;
use App\StructuralCalculation\Slabs\SlabStripAnalysisException;
use App\StructuralCalculation\Slabs\SlabStripAnalysisRejectionReason;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;

function slabStripAnalysisCalculator(): SlabStripAnalysisCalculator
{
    return app(SlabStripAnalysisCalculator::class);
}

function slabAnalysisCombination(SlabSurfaceLoadCombinationType $type, float $value): SlabSurfaceLoadCombination
{
    return new SlabSurfaceLoadCombination(
        $type,
        0,
        0,
        0,
        0,
        0,
        0,
        $value,
        match ($type) {
            SlabSurfaceLoadCombinationType::ULTIMATE => FundamentalUltimateCombinationExpression::EN1990_6_10,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC => ServiceabilityCombinationExpression::EN1990_6_14,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT => ServiceabilityCombinationExpression::EN1990_6_15,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT => ServiceabilityCombinationExpression::EN1990_6_16,
        },
        'provided by SLAB-05',
    );
}

function slabAnalysisReferenceCombinations(): SlabActionCombinations
{
    return new SlabActionCombinations(
        slabAnalysisCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 13.8),
        slabAnalysisCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 10.0),
        slabAnalysisCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 9.0),
        slabAnalysisCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, 8.6),
    );
}

it('calculates the reference linear loads and internal forces for the one-metre simply supported strip', function () {
    $result = slabStripAnalysisCalculator()->calculate(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 200),
        slabAnalysisReferenceCombinations(),
    );

    expect($result::CALCULATION_STRIP_DESCRIPTION)->toBe('bande de dalle de 1 m')
        ->and($result->linearLoads->uls->lineLoad)->toBe(13.8)
        ->and($result->linearLoads->slsCharacteristic->lineLoad)->toBe(10.0)
        ->and($result->linearLoads->slsFrequent->lineLoad)->toBe(9.0)
        ->and($result->linearLoads->slsQuasiPermanent->lineLoad)->toBe(8.6)
        ->and($result->linearLoads->uls->name)->toBe('Charge linéique ELU de la bande')
        ->and($result->linearLoads->uls->substitution)->toBe('w = 13.8 × 1')
        ->and(abs($result->internalForces->uls->maximumMoment - 43.125))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->uls->maximumShear - 34.5))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsCharacteristic->maximumMoment - 31.25))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsCharacteristic->maximumShear - 25.0))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsFrequent->maximumMoment - 28.125))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsFrequent->maximumShear - 22.5))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsQuasiPermanent->maximumMoment - 26.875))->toBeLessThan(0.000000001)
        ->and(abs($result->internalForces->slsQuasiPermanent->maximumShear - 21.5))->toBeLessThan(0.000000001)
        ->and($result->internalForces->uls::MOMENT_UNIT)->toBe('kN·m')
        ->and($result->internalForces->uls::SHEAR_UNIT)->toBe('kN')
        ->and($result->internalForces->uls::MOMENT_FORMULA)->toBe('Mmax = w × L² / 8')
        ->and($result->internalForces->uls::SHEAR_FORMULA)->toBe('Vmax = w × L / 2')
        ->and($result->internalForces->uls->momentSubstitution)->toBe('Mmax = 13.8 × 5² / 8')
        ->and($result->internalForces->uls->shearSubstitution)->toBe('Vmax = 13.8 × 5 / 2');
});

it('consumes SLAB-05 combinations without depending on material selections', function () {
    $combinations = slabAnalysisReferenceCombinations();

    $first = slabStripAnalysisCalculator()->calculate(SlabCalculationConfiguration::supported(), new SlabGeometry(5000, 200), $combinations);
    $second = slabStripAnalysisCalculator()->calculate(SlabCalculationConfiguration::supported(), new SlabGeometry(5000, 200), $combinations);

    expect($first->internalForces->uls->maximumMoment)->toBe($second->internalForces->uls->maximumMoment)
        ->and($first->internalForces->uls->maximumShear)->toBe($second->internalForces->uls->maximumShear)
        ->and(property_exists($first, 'materials'))->toBeFalse();
});

it('rejects an invalid effective span before static analysis', function () {
    slabStripAnalysisCalculator()->calculate(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(0, 200),
        slabAnalysisReferenceCombinations(),
    );
})->throws(SlabStripAnalysisException::class, SlabStripAnalysisRejectionReason::INVALID_EFFECTIVE_SPAN->value);
