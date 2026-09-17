<?php

use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
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
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposal;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalConfiguration;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalGenerator;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalResult;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalStatus;
use App\StructuralCalculation\Slabs\SlabMaterials;
use App\StructuralCalculation\Slabs\SlabSecondaryReinforcementProposalGenerator;
use App\StructuralCalculation\Slabs\SlabSecondaryReinforcementProposalStatus;
use App\StructuralCalculation\Slabs\SlabStripAnalysisCalculator;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;
use App\StructuralCalculation\Slabs\SlabUlsFlexureCalculator;

function slabSecondaryCombination(SlabSurfaceLoadCombinationType $type, float $value): SlabSurfaceLoadCombination
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

function slabSecondaryMainProposal()
{
    $input = new SlabCalculationInput(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 200),
        new SlabMaterials(ConcreteStrengthClass::C30_37, ReinforcementSteelGrade::B500B, ExposureClassCode::XC1),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );
    $combinations = new SlabActionCombinations(
        slabSecondaryCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 13.8),
        slabSecondaryCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 0),
        slabSecondaryCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 0),
        slabSecondaryCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, 0),
    );
    $analysis = app(SlabStripAnalysisCalculator::class)->calculate($input->configuration, $input->geometry, $combinations);
    $flexure = app(SlabUlsFlexureCalculator::class)->calculate($input, $analysis);

    return app(SlabMainReinforcementProposalGenerator::class)->generate($input, $analysis, $flexure);
}

function slabSecondaryMainProposalWithProvidedArea(float $providedArea): SlabMainReinforcementProposalResult
{
    $main = slabSecondaryMainProposal();
    $proposal = $main->proposal;

    if ($proposal === null) {
        throw new LogicException('The SLAB-08 reference proposal is required by this test.');
    }

    return new SlabMainReinforcementProposalResult(
        $main->initialTargetArea,
        $main->diameterCatalogue,
        $main->spacingCatalogue,
        SlabMainReinforcementProposalStatus::REINFORCEMENT_PROPOSAL_FOUND,
        new SlabMainReinforcementProposal(
            $proposal->barDiameter,
            $proposal->spacing,
            $proposal->barArea,
            $providedArea,
            $proposal->recalculatedFlexure,
            $providedArea - $proposal->recalculatedFlexure->designReinforcementArea,
        ),
        $main->generatedCandidateCount,
        $main->insufficientCandidateCount,
        $main->unsupportedCandidateCount,
    );
}

it('uses 20 percent of the provided main steel and selects a valid secondary proposal', function () {
    $main = slabSecondaryMainProposal();
    $result = app(SlabSecondaryReinforcementProposalGenerator::class)->generate(SlabCalculationConfiguration::supported(), new SlabGeometry(5000, 200), $main);

    expect($main->proposal)->not->toBeNull()
        ->and(abs($main->proposal->providedAreaPerMeter - 615.7521601035994))->toBeLessThan(0.000000001)
        ->and($main->proposal->label())->toBe('HA14 / 250 mm')
        ->and($result->mainProvidedAreaPerMeter)->toBe($main->proposal->providedAreaPerMeter)
        ->and($result->secondaryReinforcementRatio)->toBe(0.20)
        ->and(abs($result->minimumRequiredAreaPerMeter - 123.15043202071989))->toBeLessThan(0.000000001)
        ->and($result->maximumAllowedSpacing)->toBe(450.0)
        ->and($result->status)->toBe(SlabSecondaryReinforcementProposalStatus::SECONDARY_REINFORCEMENT_PROPOSAL_FOUND)
        ->and($result->proposal->label())->toBe('HA8 / 300 mm')
        ->and($result->proposal->providedAreaPerMeter)->toBeGreaterThanOrEqual($result->minimumRequiredAreaPerMeter)
        ->and($result->proposal->spacing)->toBeLessThanOrEqual($result->maximumAllowedSpacing);
});

it('uses the slab thickness for the secondary maximum spacing', function () {
    $result = app(SlabSecondaryReinforcementProposalGenerator::class)->generate(SlabCalculationConfiguration::supported(), new SlabGeometry(5000, 100), slabSecondaryMainProposal());

    expect($result->maximumAllowedSpacing)->toBe(350.0);
});

it('derives the minimum from the provided main reinforcement rather than the flexural demand', function () {
    $result = app(SlabSecondaryReinforcementProposalGenerator::class)->generate(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 200),
        slabSecondaryMainProposalWithProvidedArea(500),
    );

    expect($result->mainProvidedAreaPerMeter)->toBe(500.0)
        ->and($result->minimumRequiredAreaPerMeter)->toBe(100.0);
});

it('rejects a candidate when its spacing exceeds the profile maximum', function () {
    $generator = new SlabSecondaryReinforcementProposalGenerator(
        app(FrenchEurocodeProfileRepository::class),
        app(ReinforcementBarDiameterCatalog::class),
        new SlabMainReinforcementProposalConfiguration([400]),
        app(ReinforcementBarAreaCalculator::class),
    );
    $result = $generator->generate(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 100),
        slabSecondaryMainProposalWithProvidedArea(500),
    );

    expect($result->excessiveSpacingCandidateCount)->toBe(8)
        ->and($result->proposal)->toBeNull()
        ->and($result->status)->toBe(SlabSecondaryReinforcementProposalStatus::NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL);
});

it('does not fall back when all candidates have insufficient steel', function () {
    $generator = new SlabSecondaryReinforcementProposalGenerator(
        app(FrenchEurocodeProfileRepository::class),
        app(ReinforcementBarDiameterCatalog::class),
        new SlabMainReinforcementProposalConfiguration([300]),
        app(ReinforcementBarAreaCalculator::class),
    );
    $result = $generator->generate(
        SlabCalculationConfiguration::supported(),
        new SlabGeometry(5000, 200),
        slabSecondaryMainProposalWithProvidedArea(100000),
    );

    expect($result->insufficientAreaCandidateCount)->toBe(8)
        ->and($result->proposal)->toBeNull()
        ->and($result->status)->toBe(SlabSecondaryReinforcementProposalStatus::NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL);
});
