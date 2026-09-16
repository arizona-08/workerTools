<?php

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Slabs\SlabActionCombinations;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCalculationInput;
use App\StructuralCalculation\Slabs\SlabCalculationResultAssembler;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsCalculator;
use App\StructuralCalculation\Slabs\SlabCrackVerificationStatus;
use App\StructuralCalculation\Slabs\SlabDeflectionVerificationStatus;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposal;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalGenerator;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalResult;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposalStatus;
use App\StructuralCalculation\Slabs\SlabMaterials;
use App\StructuralCalculation\Slabs\SlabSecondaryReinforcementProposalGenerator;
use App\StructuralCalculation\Slabs\SlabServiceabilityCalculator;
use App\StructuralCalculation\Slabs\SlabStripAnalysisCalculator;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombination;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;
use App\StructuralCalculation\Slabs\SlabUlsFlexureCalculator;

function slabSlsCombination(SlabSurfaceLoadCombinationType $type, float $value): SlabSurfaceLoadCombination
{
    return new SlabSurfaceLoadCombination($type, 0, 0, 0, 0, 0, 0, $value, match ($type) {
        SlabSurfaceLoadCombinationType::ULTIMATE => FundamentalUltimateCombinationExpression::EN1990_6_10,
        SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC => ServiceabilityCombinationExpression::EN1990_6_14,
        SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT => ServiceabilityCombinationExpression::EN1990_6_15,
        SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT => ServiceabilityCombinationExpression::EN1990_6_16,
    }, 'provided by SLAB-05');
}

function slabSlsSetup(ExposureClassCode $exposure = ExposureClassCode::XC1, float $quasiPermanentLoad = 5): array
{
    $input = new SlabCalculationInput(
        SlabCalculationConfiguration::mvp(),
        new SlabGeometry(5000, 200),
        new SlabMaterials(ConcreteStrengthClass::C30_37, ReinforcementSteelGrade::B500B, $exposure),
        new SlabSurfaceLoads(0, 0, 0, 0),
    );
    $combinations = new SlabActionCombinations(
        slabSlsCombination(SlabSurfaceLoadCombinationType::ULTIMATE, 13.8),
        slabSlsCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 8),
        slabSlsCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 6),
        slabSlsCombination(SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, $quasiPermanentLoad),
    );
    $analysis = app(SlabStripAnalysisCalculator::class)->calculate($input->configuration, $input->geometry, $combinations);
    $flexure = app(SlabUlsFlexureCalculator::class)->calculate($input, $analysis);
    $main = app(SlabMainReinforcementProposalGenerator::class)->generate($input, $analysis, $flexure);

    return [$input, $analysis, $main, $combinations];
}

function slabSlsMainWithLayout(SlabMainReinforcementProposalResult $main, float $diameter, float $spacing, float $providedArea): SlabMainReinforcementProposalResult
{
    $proposal = $main->proposal;
    if ($proposal === null) {
        throw new LogicException('The reference SLAB-08 proposal is required.');
    }

    return new SlabMainReinforcementProposalResult(
        $main->initialTargetArea, $main->diameterCatalogue, $main->spacingCatalogue,
        SlabMainReinforcementProposalStatus::REINFORCEMENT_PROPOSAL_FOUND,
        new SlabMainReinforcementProposal($diameter, $spacing, pi() * $diameter ** 2 / 4, $providedArea, $proposal->recalculatedFlexure, $providedArea - $proposal->recalculatedFlexure->designReinforcementArea),
        $main->generatedCandidateCount, $main->insufficientCandidateCount, $main->unsupportedCandidateCount,
    );
}

it('uses the real SLAB-08 main reinforcement and the quasi-permanent moment from SLAB-06', function () {
    [$input, $analysis, $main] = slabSlsSetup();
    $result = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);
    $proposal = $main->proposal;

    expect($proposal)->not->toBeNull()
        ->and($proposal->barDiameter)->toBe(14.0)
        ->and($proposal->spacing)->toBe(250.0)
        ->and(abs($proposal->providedAreaPerMeter - 615.7521601035994))->toBeLessThan(1e-9)
        ->and($proposal->recalculatedFlexure->effectiveDepth->effectiveDepth)->toBe(169.0)
        ->and($proposal->recalculatedFlexure->effectiveDepth->nominalCover)->toBe(24.0)
        ->and($result->crackVerification->loadCombination)->toBe('QUASI_PERMANENT')
        ->and($result->crackVerification->serviceMoment)->toBe($analysis->internalForces->slsQuasiPermanent->maximumMoment)
        ->and(abs($result->crackVerification->modularRatio - 6.060606060606061))->toBeLessThan(1e-12)
        ->and(abs($result->crackVerification->crackedNeutralAxisDepth - 31.979308935924532))->toBeLessThan(1e-9)
        ->and(abs($result->crackVerification->crackedSecondMomentOfArea - 80965392.5446586))->toBeLessThan(1e-4)
        ->and(abs($result->crackVerification->steelStress - 160.2591406247509))->toBeLessThan(1e-9)
        ->and(abs($result->crackVerification->effectiveTensionArea - 56006.89702135849))->toBeLessThan(1e-9)
        ->and(abs($result->crackVerification->effectiveReinforcementRatio - 0.010994220227354846))->toBeLessThan(1e-12)
        ->and(abs($result->crackVerification->maximumCrackSpacing - 218.42689838329812))->toBeLessThan(1e-9)
        ->and(abs($result->crackVerification->strainDifference - 0.00048077742187425265))->toBeLessThan(1e-15)
        ->and(abs($result->crackVerification->crackWidth - 0.10501472107271144))->toBeLessThan(1e-12)
        ->and($result->crackVerification->crackWidthLimit)->toBe(0.4)
        ->and($result->crackVerification->utilization)->toBe($result->crackVerification->crackWidth / 0.4)
        ->and($result->crackVerification->status)->toBe(SlabCrackVerificationStatus::COMPLIANT)
        ->and($result->deflectionVerification->effectiveDepth)->toBe($proposal->recalculatedFlexure->effectiveDepth->effectiveDepth)
        ->and($result->deflectionVerification->actualSpanDepthRatio)->toBe($input->geometry->effectiveSpan / $proposal->recalculatedFlexure->effectiveDepth->effectiveDepth)
        ->and(abs($result->deflectionVerification->reinforcementRatio - 0.003614865431783592))->toBeLessThan(1e-12)
        ->and(abs($result->deflectionVerification->referenceReinforcementRatio - 0.005477225575051661))->toBeLessThan(1e-12)
        ->and($result->deflectionVerification->structuralFactor)->toBe(1.0)
        ->and(abs($result->deflectionVerification->steelStressCorrectionFactor - 1.0079224177760708))->toBeLessThan(1e-12)
        ->and(abs($result->deflectionVerification->allowableSpanDepthRatio - 30.167100299898486))->toBeLessThan(1e-9)
        ->and($result->deflectionVerification->method)->toBe('SIMPLIFIED_SPAN_DEPTH')
        ->and(property_exists($result->deflectionVerification, 'deflectionMm'))->toBeFalse();
});

it('rejects crack verification for an exposure without a validated crack-width limit', function () {
    [$input, $analysis, $main] = slabSlsSetup(ExposureClassCode::XC2);
    $result = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);

    expect($result->crackVerification->status)->toBe(SlabCrackVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($result->crackVerification->crackWidth)->toBeNull()
        ->and($result->crackVerification->warnings)->toContain('UNSUPPORTED_CRACK_WIDTH_EXPOSURE_CLASS');
});

it('uses the real main bar layout rather than a preliminary flexural reinforcement value', function () {
    [$input, $analysis, $main] = slabSlsSetup();
    $reference = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);
    $changed = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, slabSlsMainWithLayout($main, 8, 150, 700));

    expect($changed->crackVerification->maximumCrackSpacing)->not->toBe($reference->crackVerification->maximumCrackSpacing)
        ->and($changed->crackVerification->effectiveReinforcementRatio)->not->toBe($reference->crackVerification->effectiveReinforcementRatio)
        ->and($changed->crackVerification->crackWidth)->not->toBe($reference->crackVerification->crackWidth);
});

it('reports a local non-compliant crack width when the quasi-permanent moment exceeds the limit', function () {
    [$input, $analysis, $main] = slabSlsSetup(quasiPermanentLoad: 25);
    $result = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);

    expect($result->crackVerification->crackWidth)->toBeGreaterThan($result->crackVerification->crackWidthLimit)
        ->and($result->crackVerification->status)->toBe(SlabCrackVerificationStatus::NOT_COMPLIANT);
});

it('reports local compliant or non-compliant simplified span-depth verification without a global status', function () {
    [$input, $analysis, $main] = slabSlsSetup();
    $compliant = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);
    $longSpanInput = new SlabCalculationInput($input->configuration, new SlabGeometry(10000, 200), $input->materials, $input->loads);
    $notCompliant = app(SlabServiceabilityCalculator::class)->calculate($longSpanInput, $analysis, $main);

    expect($compliant->deflectionVerification->status)->toBe(SlabDeflectionVerificationStatus::COMPLIANT)
        ->and($notCompliant->deflectionVerification->status)->toBe(SlabDeflectionVerificationStatus::NOT_COMPLIANT)
        ->and(property_exists($compliant, 'overallStatus'))->toBeFalse();
});

it('projects existing slab results into the final structured contract without recalculating them', function () {
    [$input, $analysis, $main, $combinations] = slabSlsSetup();
    $flexure = app(SlabUlsFlexureCalculator::class)->calculate($input, $analysis);
    $secondary = app(SlabSecondaryReinforcementProposalGenerator::class)->generate($input->configuration, $input->geometry, $main);
    $serviceability = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);
    $actions = app(SlabCharacteristicActionsCalculator::class)->calculate($input->geometry, $input->loads);
    $result = app(SlabCalculationResultAssembler::class)->assemble($input, $actions, $combinations, $analysis, $flexure, $main, $secondary, $serviceability);

    expect($result->status->value)->toBe('COMPLIANT')
        ->and($result->summary->designBendingMoment)->toBe($main->proposal->recalculatedFlexure->designMoment)
        ->and($result->summary->effectiveDepth)->toBe($main->proposal->recalculatedFlexure->effectiveDepth->effectiveDepth)
        ->and($result->summary->mainReinforcement->providedAreaPerMeter)->toBe($main->proposal->providedAreaPerMeter)
        ->and($result->summary->secondaryReinforcement->providedAreaPerMeter)->toBe($secondary->proposal->providedAreaPerMeter)
        ->and($result->summary->governingVerificationType)->toBe('DEFLECTION')
        ->and($result->summary->utilization)->toBe($serviceability->deflectionVerification->utilization)
        ->and(array_map(fn ($verification) => $verification->identifier, $result->verifications))->toBe(['FLEXURE', 'MAIN_REINFORCEMENT', 'SECONDARY_REINFORCEMENT', 'CRACK', 'DEFLECTION'])
        ->and(array_keys(get_object_vars($result->details)))->toBe(['overallStatus', 'ulsStatus', 'slsStatus', 'governingVerification', 'assumptions', 'characteristicActions', 'combinations', 'internalForces', 'flexure', 'mainReinforcement', 'secondaryReinforcement', 'serviceability', 'warnings']);
});

it('gives priority to a non-compliant required serviceability verification in the final status', function () {
    [$input, $analysis, $main, $combinations] = slabSlsSetup(quasiPermanentLoad: 25);
    $flexure = app(SlabUlsFlexureCalculator::class)->calculate($input, $analysis);
    $secondary = app(SlabSecondaryReinforcementProposalGenerator::class)->generate($input->configuration, $input->geometry, $main);
    $serviceability = app(SlabServiceabilityCalculator::class)->calculate($input, $analysis, $main);
    $actions = app(SlabCharacteristicActionsCalculator::class)->calculate($input->geometry, $input->loads);
    $result = app(SlabCalculationResultAssembler::class)->assemble($input, $actions, $combinations, $analysis, $flexure, $main, $secondary, $serviceability);

    expect($serviceability->crackVerification->status)->toBe(SlabCrackVerificationStatus::NOT_COMPLIANT)
        ->and($result->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->details->slsStatus->value)->toBe('NOT_COMPLIANT');
});
