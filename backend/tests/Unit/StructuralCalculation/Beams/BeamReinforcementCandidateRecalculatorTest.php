<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamReinforcementCandidateRecalculationStatus;
use App\StructuralCalculation\Beams\BeamReinforcementCandidateRecalculator;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryCandidatesResult;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryCheckResult;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamReinforcementSpacingGoverningCriterion;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

function beamReinforcementCandidateRecalculator(): BeamReinforcementCandidateRecalculator
{
    return app(BeamReinforcementCandidateRecalculator::class);
}

function candidateRecalculationContext(): array
{
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m']],
    ]);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $selfWeight = app(SelfWeightCalculator::class)->calculate($setup->geometry, true, app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete());
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $ultimate = app(BeamUltimateCombinationCalculator::class)->calculate($actions, $profile);
    $serviceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($actions, $profile);
    $bending = app(SimplySupportedBeamBendingMomentCalculator::class)->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
    $cover = app(NominalCoverCalculator::class)->calculate(new CoverCalculationInput(CoverMode::AUTO, $setup->materials->exposureClasses, $setup->materials->concreteClass, 50, BeamFlexuralDetailingAssumptions::mvp()->transverseReinforcementDiameter), $profile);
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate($setup->configuration->calculationMode, $setup->geometry, $cover, BeamFlexuralDetailingAssumptions::mvp());
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $reduced = app(BeamReducedMomentCalculator::class)->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $neutral = app(BeamNeutralAxisCalculator::class)->calculate($reduced, $depth, $strengths->concrete);
    $lever = app(BeamLeverArmCalculator::class)->calculate($depth, $neutral);
    $required = app(BeamRequiredTensionReinforcementCalculator::class)->calculate($bending->ultimate, $strengths->steel, $lever);

    return compact('setup', 'profile', 'bending', 'cover', 'depth', 'strengths', 'required');
}

function candidateCheck(int $count, float $diameter, float $initialTargetArea = 415.06771776287212): BeamReinforcementGeometryCheckResult
{
    $provided = $count * M_PI * $diameter ** 2 / 4;
    $candidate = new BeamReinforcementProposalCandidate($count, $diameter, $provided / $count, $provided, $initialTargetArea, $provided - $initialTargetArea, $initialTargetArea / $provided);

    return new BeamReinforcementGeometryCheckResult($candidate, 204, 20, 25, BeamReinforcementSpacingGoverningCriterion::AGGREGATE_SIZE, $count * $diameter + ($count - 1) * 25, 1, true, null);
}

function recalculateCandidates(array $checks, ?float $initialRequiredArea = null): array
{
    $context = candidateRecalculationContext();
    $geometryCandidates = new BeamReinforcementGeometryCandidatesResult(204, $checks, []);
    if ($initialRequiredArea !== null) {
        $context['required'] = new BeamRequiredTensionReinforcementResult(95.45859375, 95458593.75, 500 / 1.15, 528.96131457381, (500 / 1.15) * 528.96131457381, $initialRequiredArea);
    }
    $result = beamReinforcementCandidateRecalculator()->recalculate(
        $geometryCandidates, $context['bending']->ultimate, $context['setup']->geometry, $context['cover'], BeamFlexuralDetailingAssumptions::mvp(), $context['strengths'],
        app(ConcreteClassRepository::class)->get($context['setup']->materials->concreteClass), app(ReinforcementSteelGradeRepository::class)->get($context['setup']->materials->steelGrade),
        $context['profile'], $context['depth'], $context['required'],
    );

    return [$context, $result];
}

it('recalculates the full flexural chain with candidate diameters 12 14 and 10', function () {
    [$context, $result] = recalculateCandidates([candidateCheck(4, 12), candidateCheck(3, 14), candidateCheck(6, 10)]);

    expect($result->validCandidates)->toHaveCount(3)
        ->and($result->rejectedCandidates)->toBe([])
        ->and($result->validCandidates[0]->effectiveDepth->effectiveDepth)->toBe(546.0)
        ->and($result->validCandidates[1]->effectiveDepth->effectiveDepth)->toBe(545.0)
        ->and($result->validCandidates[2]->effectiveDepth->effectiveDepth)->toBe(547.0);

    foreach ($result->validCandidates as $recalculation) {
        expect($recalculation->effectiveDepth->longitudinalBarDiameterSource->value)->toBe('CANDIDATE')
            ->and($recalculation->requiredArea->requiredReinforcementArea)->toBeLessThan($context['required']->requiredReinforcementArea)
            ->and($recalculation->providedArea)->toBeGreaterThanOrEqual($recalculation->targetArea->targetArea)
            ->and($recalculation->status)->toBe(BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION);
    }
});

it('keeps the assumed diameter depth for HA16 and decreases it for a larger candidate diameter', function () {
    [$context, $result] = recalculateCandidates([candidateCheck(3, 16), candidateCheck(2, 20)]);

    expect($result->validCandidates[0]->effectiveDepth->effectiveDepth)->toBe($context['depth']->effectiveDepth)
        ->and($result->validCandidates[1]->effectiveDepth->effectiveDepth)->toBeLessThan($context['depth']->effectiveDepth)
        ->and($result->validCandidates[1]->requiredArea->requiredReinforcementArea)->toBeGreaterThan($context['required']->requiredReinforcementArea);
});

it('rejects a fixed candidate that becomes insufficient after recalculation', function () {
    [, $result] = recalculateCandidates([candidateCheck(2, 16, 400)], 400);
    $recalculation = $result->rejectedCandidates[0];

    expect($recalculation->initialFlexuralRequiredArea)->toBe(400.0)
        ->and($recalculation->providedArea)->toBeLessThan($recalculation->targetArea->targetArea)
        ->and($recalculation->sufficientAfterRecalculation)->toBeFalse()
        ->and($recalculation->status)->toBe(BeamReinforcementCandidateRecalculationStatus::INSUFFICIENT_AFTER_RECALCULATION);
});

it('rejects a candidate explicitly when its recalculated singly reinforced domain is invalid', function () {
    $context = candidateRecalculationContext();
    $geometryCandidates = new BeamReinforcementGeometryCandidatesResult(204, [candidateCheck(2, 20)], []);
    $invalidDomainMoment = new BeamBendingMoment(
        $context['bending']->ultimate->lineLoad,
        700,
        $context['bending']->ultimate->combinationReference,
        $context['bending']->ultimate->formula,
    );

    $result = beamReinforcementCandidateRecalculator()->recalculate(
        $geometryCandidates, $invalidDomainMoment, $context['setup']->geometry, $context['cover'], BeamFlexuralDetailingAssumptions::mvp(), $context['strengths'],
        app(ConcreteClassRepository::class)->get($context['setup']->materials->concreteClass), app(ReinforcementSteelGradeRepository::class)->get($context['setup']->materials->steelGrade),
        $context['profile'], $context['depth'], $context['required'],
    );

    $recalculation = $result->rejectedCandidates[0];
    expect($result->validCandidates)->toBe([])
        ->and($recalculation->flexuralDomain->singlyReinforcedModelValid)->toBeFalse()
        ->and($recalculation->targetArea)->toBeNull()
        ->and($recalculation->sufficientAfterRecalculation)->toBeFalse()
        ->and($recalculation->status)->toBe(BeamReinforcementCandidateRecalculationStatus::INVALID_SINGLY_REINFORCED_DOMAIN);
});
