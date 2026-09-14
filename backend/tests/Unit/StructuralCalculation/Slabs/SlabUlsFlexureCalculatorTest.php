<?php

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Slabs\SlabActionCombinations;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCalculationInput;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabMaterials;
use App\StructuralCalculation\Slabs\SlabReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Slabs\SlabStripAnalysis;
use App\StructuralCalculation\Slabs\SlabStripAnalysisCalculator;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;
use App\StructuralCalculation\Slabs\SlabUlsFlexureCalculator;
use App\StructuralCalculation\Slabs\SlabUlsFlexureException;
use App\StructuralCalculation\Slabs\SlabUlsFlexureRejectionReason;

function slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType $type, float $value): SlabSurfaceLoadCombination
{
    return new SlabSurfaceLoadCombination(
        $type, 0, 0, 0, 0, 0, 0, $value,
        match ($type) {
            SlabSurfaceLoadCombinationType::ULTIMATE => FundamentalUltimateCombinationExpression::EN1990_6_10,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC => ServiceabilityCombinationExpression::EN1990_6_14,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT => ServiceabilityCombinationExpression::EN1990_6_15,
            SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT => ServiceabilityCombinationExpression::EN1990_6_16,
        },
        'provided by SLAB-05',
    );
}

function slabFlexureAnalysis(): SlabStripAnalysis
{
    $combinations = new SlabActionCombinations(
        slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 13.8),
        slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 10),
        slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 9),
        slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, 8.6),
    );

    return app(SlabStripAnalysisCalculator::class)->calculate(SlabCalculationConfiguration::mvp(), new SlabGeometry(5000, 200), $combinations);
}

function slabFlexureInput(ConcreteStrengthClass $concrete = ConcreteStrengthClass::C30_37, float $thickness = 200): SlabCalculationInput
{
    return new SlabCalculationInput(
        SlabCalculationConfiguration::mvp(),
        new SlabGeometry(5000, $thickness),
        new SlabMaterials($concrete, ReinforcementSteelGrade::B500B, ExposureClassCode::XC1),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );
}

it('sizes the C30/37 reference one-metre slab strip from the MEd supplied by SLAB-06', function () {
    $result = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(), slabFlexureAnalysis());

    expect($result->designMoment)->toBe(43.125)
        ->and($result->designMomentInNewtonMillimetres)->toBe(43125000.0)
        ->and($result::DESIGN_MOMENT_SOURCE)->toBe('SLAB-06')
        ->and($result->sectionWidth)->toBe(1000.0)
        ->and($result->effectiveDepth->overallDepth)->toBe(200.0)
        ->and($result->cover->cNom)->toBe(26.0)
        ->and($result->effectiveDepth->preliminaryMainBarDiameter)->toBe(16.0)
        ->and($result->effectiveDepth->effectiveDepth)->toBe(166.0)
        ->and($result->effectiveDepth::FORMULA)->toBe('d = h - c_nom - φ_main / 2')
        ->and($result->concreteDesignStrength)->toBe(20.0)
        ->and(abs($result->steelDesignStrength - 434.7826086956522))->toBeLessThan(0.000000001)
        ->and(abs($result->reducedMoment - 0.078249745971839))->toBeLessThan(0.000000001)
        ->and(abs($result->neutralAxisRatio - 0.10197145338715))->toBeLessThan(0.000000001)
        ->and(abs($result->neutralAxisDepth - 16.927261262268))->toBeLessThan(0.000000001)
        ->and(abs($result->leverArm - 159.22909549509))->toBeLessThan(0.000000001)
        ->and(abs($result->requiredReinforcementArea - 622.92321445145))->toBeLessThan(0.000000001)
        ->and(abs($result->minimumReinforcementArea - 250.328))->toBeLessThan(0.000000001)
        ->and($result->designReinforcementArea)->toBe($result->requiredReinforcementArea)
        ->and($result->governingRequirement)->toBe(SlabReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND)
        ->and($result::REINFORCEMENT_AREA_UNIT)->toBe('mm²/m')
        ->and(property_exists($result, 'providedReinforcementArea'))->toBeFalse();
});

it('uses the fixed one-metre width and changes the flexural demand when slab thickness changes', function () {
    $thin = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(thickness: 200), slabFlexureAnalysis());
    $thick = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(thickness: 250), slabFlexureAnalysis());

    expect($thin->sectionWidth)->toBe(1000.0)
        ->and($thick->sectionWidth)->toBe(1000.0)
        ->and($thick->effectiveDepth->effectiveDepth)->toBeGreaterThan($thin->effectiveDepth->effectiveDepth)
        ->and($thick->reducedMoment)->toBeLessThan($thin->reducedMoment)
        ->and($thick->requiredReinforcementArea)->toBeLessThan($thin->requiredReinforcementArea);
});

it('uses the selected common concrete properties instead of a C30/37 constant', function () {
    $c30 = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(ConcreteStrengthClass::C30_37), slabFlexureAnalysis());
    $c25 = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(ConcreteStrengthClass::C25_30), slabFlexureAnalysis());

    expect($c25->concreteDesignStrength)->toBeLessThan($c30->concreteDesignStrength)
        ->and($c25->meanTensileConcreteStrength)->toBeLessThan($c30->meanTensileConcreteStrength)
        ->and($c25->requiredReinforcementArea)->toBeGreaterThan($c30->requiredReinforcementArea);
});

it('keeps the EC2 minimum reinforcement as the design target when flexural demand is zero', function () {
    $zero = slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 0);
    $combinations = new SlabActionCombinations($zero, $zero, $zero, $zero);
    $analysis = app(SlabStripAnalysisCalculator::class)->calculate(SlabCalculationConfiguration::mvp(), new SlabGeometry(5000, 200), $combinations);

    $result = app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(), $analysis);

    expect($result->requiredReinforcementArea)->toBe(0.0)
        ->and($result->designReinforcementArea)->toBe($result->minimumReinforcementArea)
        ->and($result->governingRequirement)->toBe(SlabReinforcementTargetGoverningRequirement::MINIMUM_REINFORCEMENT);
});

it('explicitly refuses a flexural demand outside the singly reinforced calculation domain', function () {
    $high = slabFlexureSurfaceCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 300);
    $combinations = new SlabActionCombinations($high, $high, $high, $high);
    $analysis = app(SlabStripAnalysisCalculator::class)->calculate(SlabCalculationConfiguration::mvp(), new SlabGeometry(5000, 200), $combinations);

    app(SlabUlsFlexureCalculator::class)->calculate(slabFlexureInput(), $analysis);
})->throws(SlabUlsFlexureException::class, SlabUlsFlexureRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED->value);
