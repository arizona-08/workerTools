<?php

use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceResult;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceStatus;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignResult;
use App\StructuralCalculation\Beams\BeamShearReinforcementGoverningRequirement;
use App\StructuralCalculation\Beams\BeamStirrupProposalCandidate;
use App\StructuralCalculation\Beams\BeamStirrupProposalConfiguration;
use App\StructuralCalculation\Beams\BeamStirrupProposalGenerator;
use App\StructuralCalculation\Beams\BeamStirrupProposalRejectionReason;
use App\StructuralCalculation\Beams\BeamStirrupProposalResult;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;

function beamStirrupProposalGenerator(): BeamStirrupProposalGenerator
{
    return app(BeamStirrupProposalGenerator::class);
}

function stirrupProposalDesign(float $ved = 58.74375, float $target = 0.26290682760248, bool $requiredByDemand = false, float $width = 300): BeamShearReinforcementDesignResult
{
    return new BeamShearReinforcementDesignResult($ved, 63.862728452911, $requiredByDemand, $width, 531.019606207552, 500, 500 / 1.15, 2.5, 0.000876356092008, $requiredByDemand ? $target : 0, 0.26290682760248, $target, $requiredByDemand ? BeamShearReinforcementGoverningRequirement::SHEAR_DEMAND : BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT, $ved);
}

function stirrupProposalMaximum(bool $valid = true, float $ved = 58.74375): BeamMaximumShearResistanceResult
{
    return new BeamMaximumShearResistanceResult($ved, 300, 531.019606207552, 30, 20, 1, 0.528, 2.5, 0.4, 580.093142229491, $ved / 580.093142229491, $valid ? BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK : BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED);
}

function stirrupProposalDepth(float $depth = 546): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, $depth);
}

function stirrupProposalCover(float $cover = 40): CoverCalculationResult
{
    return new CoverCalculationResult(
        coverMode: CoverMode::AUTO,
        initialStructuralClass: null,
        structuralClassModifiers: [],
        finalStructuralClass: null,
        exposureResults: [],
        governingExposureClass: null,
        cMinBond: null,
        cMinDurability: null,
        deltaCDurGamma: null,
        deltaCDurSt: null,
        deltaCDurAdd: null,
        correctedCMinDurability: null,
        minimumAbsoluteCover: null,
        cMin: $cover,
        governingCriterion: null,
        deltaCDev: null,
        cNom: $cover,
        unit: 'mm',
        warnings: [],
    );
}

function generateStirrupProposals(?BeamStirrupProposalConfiguration $configuration = null, ?BeamShearReinforcementDesignResult $design = null, ?BeamMaximumShearResistanceResult $maximum = null, ?BeamEffectiveDepthResult $depth = null): BeamStirrupProposalResult
{
    return beamStirrupProposalGenerator()->generate($design ?? stirrupProposalDesign(), $maximum ?? stirrupProposalMaximum(), $depth ?? stirrupProposalDepth(), stirrupProposalCover(), app(FrenchEurocodeProfileRepository::class)->get(), $configuration);
}

function stirrupCandidate(BeamStirrupProposalResult $result, float $diameter, float $spacing): ?BeamStirrupProposalCandidate
{
    return collect([...$result->acceptedCandidates, ...$result->rejectedCandidates])->first(fn ($candidate): bool => $candidate->barDiameter === $diameter && $candidate->spacing === $spacing);
}

it('generates and deterministically recommends a discrete stirrup proposal for the current case', function () {
    $result = generateStirrupProposals();
    $twoHaSix200 = stirrupCandidate($result, 6, 200);
    $twoHaEight350 = stirrupCandidate($result, 8, 350);

    expect($result->maximumShearResistanceValid)->toBeTrue()
        ->and($result->recommendedCandidate)->not->toBeNull()
        ->and($result->recommendedCandidate->barDiameter)->toBe(6.0)
        ->and($result->recommendedCandidate->legCount)->toBe(2)
        ->and($result->recommendedCandidate->spacing)->toBe(200.0)
        ->and(abs($twoHaSix200->providedArea - 56.548667764616))->toBeLessThan(1e-12)
        ->and($twoHaSix200->providedAreaPerLength)->toBeGreaterThan($twoHaSix200->targetAreaPerLength)
        ->and($twoHaSix200->maximumLongitudinalSpacing)->toBe(409.5)
        ->and($twoHaSix200->maximumTransverseLegSpacing)->toBe(409.5)
        ->and($twoHaSix200->accepted)->toBeTrue()
        ->and($twoHaEight350->accepted)->toBeTrue();
});

it('rejects insufficient reinforcement even when longitudinal spacing is permitted', function () {
    $result = generateStirrupProposals();

    foreach ([[6, 225], [8, 400]] as [$diameter, $spacing]) {
        $candidate = stirrupCandidate($result, $diameter, $spacing);
        expect($candidate->spacing)->toBeLessThanOrEqual($candidate->maximumLongitudinalSpacing)
            ->and($candidate->providedAreaPerLength)->toBeLessThan($candidate->targetAreaPerLength)
            ->and($candidate->rejectionReasons)->toContain(BeamStirrupProposalRejectionReason::INSUFFICIENT_REINFORCEMENT_PER_LENGTH);
    }
});

it('rejects a sufficient candidate that exceeds the longitudinal spacing limit', function () {
    $result = generateStirrupProposals(new BeamStirrupProposalConfiguration([12], 2, [450]));
    $candidate = stirrupCandidate($result, 12, 450);

    expect($candidate->providedAreaPerLength)->toBeGreaterThan($candidate->targetAreaPerLength)
        ->and($candidate->rejectionReasons)->toContain(BeamStirrupProposalRejectionReason::LONGITUDINAL_SPACING_EXCEEDED);
});

it('rejects transverse branch geometry exceeding its explicit maximum', function () {
    $design = stirrupProposalDesign(width: 1000);
    $result = generateStirrupProposals(new BeamStirrupProposalConfiguration([6], 2, [200]), $design);
    $candidate = stirrupCandidate($result, 6, 200);

    expect($candidate->transverseLegSpacing)->toBeGreaterThan($candidate->maximumTransverseLegSpacing)
        ->and($candidate->rejectionReasons)->toContain(BeamStirrupProposalRejectionReason::TRANSVERSE_LEG_SPACING_EXCEEDED);
});

it('does not propose stirrups when VRd,max is exceeded', function () {
    $result = generateStirrupProposals(null, null, stirrupProposalMaximum(false, 700));

    expect($result->maximumShearResistanceValid)->toBeFalse()
        ->and($result->recommendedCandidate)->toBeNull()
        ->and($result->acceptedCandidates)->toBe([])
        ->and($result->rejectedCandidates[0]->rejectionReasons)->toContain(BeamStirrupProposalRejectionReason::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED);
});

it('satisfies VEd with VRd,s when shear demand governs', function () {
    $design = stirrupProposalDesign(500, 0.866258033832759, true);
    $result = generateStirrupProposals(null, $design, stirrupProposalMaximum(true, 500));

    expect($result->recommendedCandidate)->not->toBeNull()
        ->and($result->recommendedCandidate->providedAreaPerLength)->toBeGreaterThanOrEqual($design->requiredShearReinforcementPerLength)
        ->and($result->recommendedCandidate->providedShearResistance)->toBeGreaterThanOrEqual($design->designShearForce);
});
