<?php

use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesGenerator;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesResult;
use App\StructuralCalculation\Beams\BeamReinforcementCandidatesStatus;
use App\StructuralCalculation\Beams\BeamReinforcementDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryCandidatesResult;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryFilter;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryRejection;
use App\StructuralCalculation\Beams\BeamReinforcementGeometryRejectionReason;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamReinforcementProposalConfiguration;
use App\StructuralCalculation\Beams\BeamReinforcementSpacingGoverningCriterion;
use App\StructuralCalculation\Beams\BeamReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Beams\BeamRequiredReinforcementAreaResult;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

function beamReinforcementGeometryFilter(): BeamReinforcementGeometryFilter
{
    return app(BeamReinforcementGeometryFilter::class);
}

function geometryCover(float $nominalCover = 40): CoverCalculationResult
{
    return new CoverCalculationResult(
        coverMode: CoverMode::AUTO, initialStructuralClass: null, structuralClassModifiers: [], finalStructuralClass: null,
        exposureResults: [], governingExposureClass: null, cMinBond: null, cMinDurability: null, deltaCDurGamma: null,
        deltaCDurSt: null, deltaCDurAdd: null, correctedCMinDurability: null, minimumAbsoluteCover: null,
        cMin: $nominalCover, governingCriterion: null, deltaCDev: null, cNom: $nominalCover, unit: 'mm', warnings: [],
    );
}

function geometryCandidate(int $count, float $diameter): BeamReinforcementProposalCandidate
{
    $provided = $count * M_PI * $diameter ** 2 / 4;

    return new BeamReinforcementProposalCandidate($count, $diameter, $provided / $count, $provided, 415.06771776287212, $provided - 415.06771776287212, 415.06771776287212 / $provided);
}

function geometryCandidates(array $candidates): BeamReinforcementCandidatesResult
{
    return new BeamReinforcementCandidatesResult(415.06771776287212, [8, 10, 12, 14, 16, 20, 25, 32], 2, 8, BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE, $candidates, count($candidates));
}

function geometryFilterResult(BeamReinforcementCandidatesResult $candidates, float $width = 300): BeamReinforcementGeometryCandidatesResult
{
    return beamReinforcementGeometryFilter()->filter(
        $candidates, new BeamGeometry(6500, $width, 600), geometryCover(), BeamFlexuralDetailingAssumptions::supported(),
        new BeamReinforcementDetailingAssumptions, app(FrenchEurocodeProfileRepository::class)->get()->reinforcementSpacingRequirements,
    );
}

it('calculates the available width and accepts the reference one-layer candidates', function () {
    $result = geometryFilterResult(geometryCandidates([geometryCandidate(4, 12), geometryCandidate(3, 14), geometryCandidate(6, 10)]));

    expect($result->availableWidth)->toBe(204.0)
        ->and($result->acceptedCandidates)->toHaveCount(3)
        ->and($result->rejectedCandidates)->toBe([])
        ->and($result->acceptedCandidates[0]->minimumClearSpacing)->toBe(25.0)
        ->and($result->acceptedCandidates[0]->spacingGoverningCriterion)->toBe(BeamReinforcementSpacingGoverningCriterion::AGGREGATE_SIZE)
        ->and($result->acceptedCandidates[0]->requiredWidth)->toBe(123.0)
        ->and($result->acceptedCandidates[0]->remainingWidth)->toBe(81.0)
        ->and($result->acceptedCandidates[1]->requiredWidth)->toBe(92.0)
        ->and($result->acceptedCandidates[2]->requiredWidth)->toBe(185.0)
        ->and($result->acceptedCandidates[2]->remainingWidth)->toBe(19.0);
});

it('keeps a too-wide candidate traceable with its horizontal-space rejection', function () {
    $result = geometryFilterResult(geometryCandidates([geometryCandidate(8, 16)]));
    $rejected = $result->rejectedCandidates[0];

    expect($result->acceptedCandidates)->toBe([])
        ->and($rejected->minimumClearSpacing)->toBe(25.0)
        ->and($rejected->requiredWidth)->toBe(303.0)
        ->and($rejected->remainingWidth)->toBe(-99.0)
        ->and($rejected->geometricallyAdmissible)->toBeFalse()
        ->and($rejected->rejectionReason)->toBe(BeamReinforcementGeometryRejectionReason::INSUFFICIENT_HORIZONTAL_SPACE);
});

it('uses the bar-diameter term when it governs the minimum clear spacing', function () {
    $result = geometryFilterResult(geometryCandidates([geometryCandidate(2, 32)]));
    $check = $result->acceptedCandidates[0];

    expect($check->minimumClearSpacing)->toBe(32.0)
        ->and($check->spacingGoverningCriterion)->toBe(BeamReinforcementSpacingGoverningCriterion::BAR_DIAMETER);
});

it('rejects a section with no positive width between stirrup branches', function () {
    try {
        geometryFilterResult(geometryCandidates([geometryCandidate(4, 12)]), 96);
    } catch (BeamReinforcementGeometryRejection $exception) {
        expect($exception->reason)->toBe('NON_POSITIVE_AVAILABLE_WIDTH');

        return;
    }

    throw new RuntimeException('Expected a section without available internal width to be rejected.');
});

it('filters the candidates generated by BEAM-REBAR-02 without changing their ranking', function () {
    $target = new BeamRequiredReinforcementAreaResult(415.06771776287212, 246.1056, 415.06771776287212, BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND);
    $candidates = new BeamReinforcementCandidatesGenerator(app(ReinforcementBarDiameterCatalog::class), new BeamReinforcementProposalConfiguration)->generate($target);
    $result = geometryFilterResult($candidates);

    expect($result->availableWidth)->toBe(204.0)
        ->and($result->acceptedCandidates[0]->candidate->barCount)->toBe(4)
        ->and($result->acceptedCandidates[0]->candidate->barDiameter)->toBe(12.0)
        ->and($result->acceptedCandidates[1]->candidate->barCount)->toBe(3)
        ->and($result->acceptedCandidates[1]->candidate->barDiameter)->toBe(14.0)
        ->and($result->acceptedCandidates[2]->candidate->barCount)->toBe(6)
        ->and($result->acceptedCandidates[2]->candidate->barDiameter)->toBe(10.0)
        ->and(collect($result->rejectedCandidates)->contains(fn ($check): bool => $check->candidate->barCount === 8 && $check->candidate->barDiameter === 16.0))->toBeTrue();
});

it('uses the EC2-05 nominal cover and existing transverse-detailing assumption', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $cover = app(NominalCoverCalculator::class)->calculate(
        new CoverCalculationInput(CoverMode::AUTO, [ExposureClassCode::XC4], ConcreteStrengthClass::C30_37, 50, BeamFlexuralDetailingAssumptions::supported()->transverseReinforcementDiameter),
        $profile,
    );
    $result = beamReinforcementGeometryFilter()->filter(
        geometryCandidates([geometryCandidate(4, 12)]), new BeamGeometry(6500, 300, 600), $cover,
        BeamFlexuralDetailingAssumptions::supported(), new BeamReinforcementDetailingAssumptions, $profile->reinforcementSpacingRequirements,
    );

    expect($cover->cNom)->toBe(40.0)
        ->and($result->availableWidth)->toBe(204.0)
        ->and($result->acceptedCandidates[0]->geometricallyAdmissible)->toBeTrue();
});
