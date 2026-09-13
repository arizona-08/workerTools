<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamDeflectionFormulaBranch;
use App\StructuralCalculation\Beams\BeamDeflectionVerificationCalculator;
use App\StructuralCalculation\Beams\BeamDeflectionVerificationException;
use App\StructuralCalculation\Beams\BeamDeflectionVerificationStatus;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralDomainCheckResult;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLeverArmResult;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamNeutralAxisResult;
use App\StructuralCalculation\Beams\BeamReducedMomentResult;
use App\StructuralCalculation\Beams\BeamReinforcementCandidateRecalculationResult;
use App\StructuralCalculation\Beams\BeamReinforcementCandidateRecalculationStatus;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamReinforcementTargetGoverningRequirement;
use App\StructuralCalculation\Beams\BeamRequiredReinforcementAreaResult;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\MinimumTensionReinforcementGoverningCriterion;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\SteelDuctilityClass;

function beamDeflectionVerificationCalculator(): BeamDeflectionVerificationCalculator
{
    return app(BeamDeflectionVerificationCalculator::class);
}

function deflectionCandidate(float $requiredArea = 413.46, float $providedArea = 452.3893421169302, BeamReinforcementCandidateRecalculationStatus $status = BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION): BeamReinforcementCandidateRecalculationResult
{
    $depth = new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546);
    $candidate = new BeamReinforcementProposalCandidate(4, 12, M_PI * 12 ** 2 / 4, $providedArea, $requiredArea, $providedArea - $requiredArea, $requiredArea / $providedArea);
    $required = new BeamRequiredTensionReinforcementResult(0, 0, 434.7826086956522, 500, 217391.3043478261, $requiredArea);
    $minimum = new BeamMinimumTensionReinforcementResult(ConcreteStrengthClass::C30_37, 2.9, ReinforcementSteelGrade::B500B, 500, 300, 546, 100, 100, 100, MinimumTensionReinforcementGoverningCriterion::FCTM_FYK);
    $domain = new BeamFlexuralDomainCheckResult(30, 0.0035, 434.7826086956522, 200000, 0.002173913, 546, 100, 0.18, 0.01, 0.6, true, true);

    return new BeamReinforcementCandidateRecalculationResult(
        $candidate,
        546,
        $requiredArea,
        $depth,
        new BeamReducedMomentResult(0, 0, 300, 546, 20, 1, 0),
        new BeamNeutralAxisResult(0, 30, 0.8, 1, 1, 0.18, 546, 100),
        new BeamLeverArmResult(546, 100, 0.18, 0.8, 80, 40, 506),
        $required,
        $minimum,
        $domain,
        new BeamRequiredReinforcementAreaResult($requiredArea, 100, $requiredArea, BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND),
        $providedArea,
        $status === BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION,
        $status,
    );
}

function deflectionConfiguration(BeamSupportSystem $supportSystem = BeamSupportSystem::SIMPLY_SUPPORTED): BeamCalculationConfiguration
{
    $reference = BeamCalculationConfiguration::mvp();

    return new BeamCalculationConfiguration($reference->calculationMode, $reference->elementType, $reference->materialType, $reference->sectionType, $supportSystem, $reference->loadModel, $reference->designCodeProfile, $reference->designSituation);
}

function calculateDeflection(BeamReinforcementCandidateRecalculationResult $candidate, ?BeamCalculationConfiguration $configuration = null, ?ReinforcementSteelProperties $steel = null)
{
    return beamDeflectionVerificationCalculator()->calculate(
        $configuration ?? deflectionConfiguration(),
        new BeamGeometry(6500, 300, 600),
        $candidate,
        app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37),
        $steel ?? app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B),
        app(FrenchEurocodeProfileRepository::class)->get(),
    );
}

it('verifies the current candidate with the simplified span-depth method', function () {
    $result = calculateDeflection(deflectionCandidate());

    expect($result->effectiveSpan)->toBe(6500.0)
        ->and($result->effectiveDepth)->toBe(546.0)
        ->and(abs($result->actualSpanDepthRatio - 6500 / 546))->toBeLessThan(1e-12)
        ->and(abs($result->reinforcementRatio - 413.46 / (300 * 546)))->toBeLessThan(1e-15)
        ->and($result->reinforcementRatio)->not->toBe($result->providedReinforcementArea / (300 * 546))
        ->and(abs($result->referenceReinforcementRatio - sqrt(30) * 0.001))->toBeLessThan(1e-15)
        ->and($result->formulaBranch)->toBe(BeamDeflectionFormulaBranch::LOW_REINFORCEMENT_RATIO)
        ->and($result->structuralFactor)->toBe(1.0)
        ->and($result->status)->toBe(BeamDeflectionVerificationStatus::COMPLIANT)
        ->and(property_exists($result, 'deflectionMm'))->toBeFalse();
});

it('uses the high-reinforcement equation and the deterministic boundary branch', function () {
    $high = calculateDeflection(deflectionCandidate(1200, 1300));
    $boundaryRequired = sqrt(30) * 0.001 * 300 * 546;
    $boundary = calculateDeflection(deflectionCandidate($boundaryRequired, $boundaryRequired));

    expect($high->formulaBranch)->toBe(BeamDeflectionFormulaBranch::HIGH_REINFORCEMENT_RATIO)
        ->and(abs($high->baseAllowableSpanDepthRatio - (11 + 1.5 * sqrt(30) * $high->referenceReinforcementRatio / $high->reinforcementRatio)))->toBeLessThan(1e-12)
        ->and($boundary->formulaBranch)->toBe(BeamDeflectionFormulaBranch::LOW_REINFORCEMENT_RATIO);
});

it('derives the steel correction from fyk and the two distinct reinforcement areas', function () {
    $same = calculateDeflection(deflectionCandidate(400, 400));
    $otherSteel = new ReinforcementSteelProperties(ReinforcementSteelGrade::B500B, 400, 200000, SteelDuctilityClass::B);
    $other = calculateDeflection(deflectionCandidate(400, 500), steel: $otherSteel);

    expect($same->steelStressCorrectionFactor)->toBe(1.0)
        ->and(abs($other->steelStressCorrectionFactor - (500 / 400) * (500 / 400)))->toBeLessThan(1e-12);
});

it('refuses insufficient or unsupported candidates rather than reporting compliance', function () {
    expect(fn () => calculateDeflection(deflectionCandidate(500, 400)))
        ->toThrow(BeamDeflectionVerificationException::class, 'INSUFFICIENT_LONGITUDINAL_REINFORCEMENT');
    expect(fn () => calculateDeflection(deflectionCandidate(status: BeamReinforcementCandidateRecalculationStatus::INVALID_SINGLY_REINFORCED_DOMAIN)))
        ->toThrow(BeamDeflectionVerificationException::class, 'CALCULATION_METHOD_NOT_SUPPORTED');
    expect(fn () => calculateDeflection(deflectionCandidate(), deflectionConfiguration(BeamSupportSystem::CONTINUOUS)))
        ->toThrow(BeamDeflectionVerificationException::class, 'CALCULATION_METHOD_NOT_SUPPORTED');
});
