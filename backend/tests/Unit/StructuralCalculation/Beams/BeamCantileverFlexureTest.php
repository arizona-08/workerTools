<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamFlexuralSteelDesignStrength;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLeverArmResult;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesGenerator;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Beams\BeamRequiredReinforcementAreaResult;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamTensionFace;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

function cantileverFlexureMoment(float $value): BeamBendingMoment
{
    return new BeamBendingMoment(10, $value, FundamentalUltimateCombinationExpression::EN1990_6_10, 'M');
}

it('represents bottom tension for a simply supported beam and top tension for a cantilever', function () {
    expect(BeamSubmodule::BEAM_SIMPLE_RECTANGULAR->tensionFace())->toBe(BeamTensionFace::BOTTOM)
        ->and(BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR->tensionFace())->toBe(BeamTensionFace::TOP)
        ->and(BeamTensionFace::TOP->reinforcementPosition())->toBe(BeamReinforcementPosition::TOP);
});

it('keeps the same useful depth for symmetric top and bottom one-layer reinforcement', function () {
    $cover = app(NominalCoverCalculator::class)->calculate(new CoverCalculationInput(coverMode: CoverMode::MANUAL, manualNominalCover: 30), app(FrenchEurocodeProfileRepository::class)->get());
    $calculator = app(BeamEffectiveDepthCalculator::class);
    $bottom = $calculator->calculate(BeamCalculationMode::DESIGN, new BeamGeometry(4000, 300, 600), $cover, BeamFlexuralDetailingAssumptions::supported(), tensionFace: BeamTensionFace::BOTTOM);
    $top = $calculator->calculate(BeamCalculationMode::DESIGN, new BeamGeometry(4000, 300, 600), $cover, BeamFlexuralDetailingAssumptions::supported(), tensionFace: BeamTensionFace::TOP);

    expect($bottom->effectiveDepth)->toBe($top->effectiveDepth)
        ->and($bottom->tensionFace)->toBe(BeamTensionFace::BOTTOM)
        ->and($top->tensionFace)->toBe(BeamTensionFace::TOP);
});

it('uses the magnitude of signed cantilever MEd for the shared flexural resistance equations', function () {
    $calculator = app(BeamRequiredTensionReinforcementCalculator::class);
    $steel = new BeamFlexuralSteelDesignStrength(ReinforcementSteelGrade::B500B, 500, 1.15, 500 / 1.15);
    $leverArm = new BeamLeverArmResult(546, 20, 20 / 546, 0.8, 16, 8, 530);
    $positive = $calculator->calculate(cantileverFlexureMoment(80), $steel, $leverArm);
    $negative = $calculator->calculate(cantileverFlexureMoment(-80), $steel, $leverArm);

    expect($negative->signedDesignMoment)->toBe(-80.0)
        ->and($negative->designMoment)->toBe(80.0)
        ->and($negative->requiredReinforcementArea)->toBe($positive->requiredReinforcementArea);
});

it('returns only top-positioned longitudinal proposals for a cantilever target', function () {
    $target = new BeamRequiredReinforcementAreaResult(415.06771776287212, 246.1056, 415.06771776287212, BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND);
    $candidates = app(BeamReinforcementCandidatesGenerator::class)->generate($target, BeamReinforcementPosition::TOP);

    expect($candidates->candidates)->not->toBeEmpty();
    foreach ($candidates->candidates as $candidate) {
        expect($candidate->position)->toBe(BeamReinforcementPosition::TOP);
    }
});
