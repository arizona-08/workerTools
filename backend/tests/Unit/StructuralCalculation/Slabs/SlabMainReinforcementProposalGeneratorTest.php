<?php

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarAreaCalculator;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Slabs\SlabActionCombinations;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCalculationInput;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalConfiguration;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalGenerator;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalStatus;
use App\StructuralCalculation\Slabs\SlabMaterials;
use App\StructuralCalculation\Slabs\SlabStripAnalysisCalculator;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;
use App\StructuralCalculation\Slabs\SlabUlsFlexureCalculator;

function slabProposalCombination(SlabSurfaceLoadCombinationType $type, float $value): SlabSurfaceLoadCombination
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

function slabProposalSetup(float $uls = 13.8): array
{
    $input = new SlabCalculationInput(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 200),
        new SlabMaterials(ConcreteStrengthClass::C30_37, ReinforcementSteelGrade::B500B, ExposureClassCode::XC1),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );
    $combinations = new SlabActionCombinations(
        slabProposalCombination(SlabSurfaceLoadCombinationType::ULTIMATE, $uls),
        slabProposalCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 0),
        slabProposalCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 0),
        slabProposalCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, 0),
    );
    $analysis = app(SlabStripAnalysisCalculator::class)->calculate($input->configuration, $input->geometry, $combinations);
    $flexure = app(SlabUlsFlexureCalculator::class)->calculate($input, $analysis);

    return [$input, $analysis, $flexure];
}

it('calculates the exact bar area and provided steel area per metre for HA10 at 150 mm', function () {
    $area = app(ReinforcementBarAreaCalculator::class)->calculate(10);

    expect(abs($area - 78.53981633974483))->toBeLessThan(0.000000001)
        ->and(abs($area * 1000 / 150 - 523.5987755982989))->toBeLessThan(0.000000001);
});

it('selects the least over-provisioned candidate after recalculating flexure with the selected diameter', function () {
    [$input, $analysis, $flexure] = slabProposalSetup();
    $result = app(SlabMainReinforcementProposalGenerator::class)->generate($input, $analysis, $flexure);

    expect($result->status)->toBe(SlabMainReinforcementProposalStatus::REINFORCEMENT_PROPOSAL_FOUND)
        ->and($result->proposal)->not->toBeNull()
        ->and($result->proposal->label())->toBe('HA14 / 250 mm')
        ->and($result->proposal->providedAreaPerMeter)->toBeGreaterThanOrEqual($result->proposal->recalculatedFlexure->designReinforcementArea)
        ->and($result->proposal->recalculatedFlexure->effectiveDepth->preliminaryMainBarDiameter)->toBe($result->proposal->barDiameter)
        ->and($result->proposal->overProvision)->toBe($result->proposal->providedAreaPerMeter - $result->proposal->recalculatedFlexure->designReinforcementArea)
        ->and($result->insufficientCandidateCount)->toBeGreaterThan(0);
});

it('does not produce a fallback proposal when every candidate is insufficient', function () {
    [$input, $analysis, $flexure] = slabProposalSetup(60);
    $generator = new SlabMainReinforcementProposalGenerator(
        app(ReinforcementBarDiameterCatalog::class),
        new SlabMainReinforcementProposalConfiguration([300]),
        app(ReinforcementBarAreaCalculator::class),
        app(SlabUlsFlexureCalculator::class),
    );
    $result = $generator->generate($input, $analysis, $flexure);

    expect($result->status)->toBe(SlabMainReinforcementProposalStatus::NO_VALID_REINFORCEMENT_PROPOSAL)
        ->and($result->proposal)->toBeNull();
});
