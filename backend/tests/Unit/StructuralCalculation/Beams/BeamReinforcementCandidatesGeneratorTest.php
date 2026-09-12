<?php

use App\StructuralCalculation\Beams\BeamReinforcementCandidatesException;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesGenerator;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesRejectionReason;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesResult;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesStatus;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamReinforcementProposalConfiguration;
use App\StructuralCalculation\Beams\BeamReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Beams\BeamRequiredReinforcementAreaResult;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

function beamReinforcementCandidatesGenerator(?BeamReinforcementProposalConfiguration $configuration = null): BeamReinforcementCandidatesGenerator
{
    return new BeamReinforcementCandidatesGenerator(
        app(ReinforcementBarDiameterCatalog::class),
        $configuration ?? new BeamReinforcementProposalConfiguration,
    );
}

function currentReinforcementTarget(float $area = 415.06771776287212): BeamRequiredReinforcementAreaResult
{
    return new BeamRequiredReinforcementAreaResult($area, 246.1056, $area, BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND);
}

function reinforcementCandidate(BeamReinforcementCandidatesResult $result, int $barCount, float $diameter): ?BeamReinforcementProposalCandidate
{
    foreach ($result->candidates as $candidate) {
        if ($candidate->barCount === $barCount && $candidate->barDiameter === $diameter) {
            return $candidate;
        }
    }

    return null;
}

it('generates area-sufficient candidates from the central catalogue for the current target', function () {
    $result = beamReinforcementCandidatesGenerator()->generate(currentReinforcementTarget());
    $threeHa14 = reinforcementCandidate($result, 3, 14);
    $twoHa16 = reinforcementCandidate($result, 2, 16);
    $twoHa20 = reinforcementCandidate($result, 2, 20);

    expect($result->catalogueUsed)->toBe([8.0, 10.0, 12.0, 14.0, 16.0, 20.0, 25.0, 32.0])
        ->and($result->minimumBarCount)->toBe(2)
        ->and($result->maximumBarCount)->toBe(8)
        ->and($result->status)->toBe(BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE)
        ->and($result->candidateCount)->toBe(count($result->candidates))
        ->and($twoHa16)->toBeNull()
        ->and($threeHa14)->not->toBeNull()
        ->and(abs($threeHa14->barArea - M_PI * 14 ** 2 / 4))->toBeLessThan(0.000000001)
        ->and(abs($threeHa14->providedArea - 3 * M_PI * 14 ** 2 / 4))->toBeLessThan(0.000000001)
        ->and($twoHa20)->not->toBeNull()
        ->and(abs($twoHa20->providedArea - 2 * M_PI * 20 ** 2 / 4))->toBeLessThan(0.000000001);

    foreach ($result->candidates as $candidate) {
        expect($candidate->providedArea)->toBeGreaterThanOrEqual($result->targetArea)
            ->and(abs($candidate->excessArea - ($candidate->providedArea - $result->targetArea)))->toBeLessThan(0.000000001)
            ->and(abs($candidate->utilizationRatio - ($result->targetArea / $candidate->providedArea)))->toBeLessThan(0.000000001)
            ->and($candidate->utilizationRatio)->toBeGreaterThan(0)
            ->and($candidate->utilizationRatio)->toBeLessThanOrEqual(1.0);
    }
});

it('orders candidates by excess then bar count then diameter', function () {
    $result = beamReinforcementCandidatesGenerator()->generate(currentReinforcementTarget());

    foreach (array_slice($result->candidates, 1) as $index => $candidate) {
        $previous = $result->candidates[$index];
        $comparison = $previous->excessArea <=> $candidate->excessArea
            ?: $previous->barCount <=> $candidate->barCount
            ?: $previous->barDiameter <=> $candidate->barDiameter;

        expect($comparison)->toBeLessThanOrEqual(0);
    }

    expect($result->candidates[0]->barCount)->toBe(4)
        ->and($result->candidates[0]->barDiameter)->toBe(12.0);
});

it('returns an explicit empty result when the configured catalogue cannot meet the target', function () {
    $result = beamReinforcementCandidatesGenerator()->generate(currentReinforcementTarget(100000));

    expect($result->status)->toBe(BeamReinforcementCandidatesStatus::NO_REINFORCEMENT_CANDIDATE)
        ->and($result->candidateCount)->toBe(0)
        ->and($result->candidates)->toBe([]);
});

it('keeps the configured minimum two bars even when the target is zero', function () {
    $result = beamReinforcementCandidatesGenerator()->generate(currentReinforcementTarget(0));

    expect($result->status)->toBe(BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE)
        ->and($result->candidates[0]->barCount)->toBe(2)
        ->and($result->candidates[0]->barDiameter)->toBe(8.0)
        ->and($result->candidates[0]->utilizationRatio)->toBe(0.0);
});

it('rejects invalid target areas and bar-count configurations', function (BeamRequiredReinforcementAreaResult $target, BeamReinforcementProposalConfiguration $configuration, BeamReinforcementCandidatesRejectionReason $reason) {
    try {
        beamReinforcementCandidatesGenerator($configuration)->generate($target);
    } catch (BeamReinforcementCandidatesException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid reinforcement-candidate generation input to be rejected.');
})->with([
    'negative target area' => [currentReinforcementTarget(-1), new BeamReinforcementProposalConfiguration, BeamReinforcementCandidatesRejectionReason::INVALID_TARGET_AREA],
    'one-bar configuration' => [currentReinforcementTarget(), new BeamReinforcementProposalConfiguration(1, 8), BeamReinforcementCandidatesRejectionReason::INVALID_MINIMUM_TENSION_BAR_COUNT],
    'greater-than-eight configuration' => [currentReinforcementTarget(), new BeamReinforcementProposalConfiguration(2, 9), BeamReinforcementCandidatesRejectionReason::INVALID_MAXIMUM_TENSION_BAR_COUNT],
]);
