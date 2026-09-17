<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamBendingMomentResult;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamCrackLoadDuration;
use App\StructuralCalculation\Beams\BeamCrackSpacingFormulaCriterion;
use App\StructuralCalculation\Beams\BeamCrackStrainDifferenceCriterion;
use App\StructuralCalculation\Beams\BeamCrackVerificationCalculator;
use App\StructuralCalculation\Beams\BeamCrackVerificationException;
use App\StructuralCalculation\Beams\BeamCrackVerificationStatus;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamServiceStressVerificationCalculator;
use App\StructuralCalculation\Beams\BeamStirrupProposalCandidate;
use App\StructuralCalculation\Beams\BeamStirrupProposalResult;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

function crackVerificationCalculator(): BeamCrackVerificationCalculator
{
    return app(BeamCrackVerificationCalculator::class);
}

function crackMoment(float $value, FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $reference): BeamBendingMoment
{
    return new BeamBendingMoment(0, $value, $reference, 'M');
}

function crackStirrup(float $diameter = 6): BeamStirrupProposalResult
{
    $candidate = new BeamStirrupProposalCandidate($diameter, 2, M_PI * $diameter ** 2 / 4, M_PI * $diameter ** 2 / 2, 200, 0.141371669, 0.1, 0.041371669, 0.7, 200, 400, 200, 400, 100, true, []);

    return new BeamStirrupProposalResult($candidate, [$candidate], [], true);
}

function crackVerificationContext(float $width = 300, float $quasiMoment = 55.7171875, ExposureClassCode $exposureClass = ExposureClassCode::XC1): array
{
    $area = 4 * M_PI * 12 ** 2 / 4;
    $geometry = new BeamGeometry(6500, $width, 600);
    $depth = new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $moments = new BeamBendingMomentResult(6.5, BeamSupportSystem::SIMPLY_SUPPORTED, BeamLoadModel::UNIFORMLY_DISTRIBUTED, 0.125, 3.25, crackMoment(0, FundamentalUltimateCombinationExpression::EN1990_6_10), crackMoment(68.65625, ServiceabilityCombinationExpression::EN1990_6_14), crackMoment(0, ServiceabilityCombinationExpression::EN1990_6_15), crackMoment($quasiMoment, ServiceabilityCombinationExpression::EN1990_6_16));

    return [
        'geometry' => $geometry,
        'depth' => $depth,
        'longitudinal' => new BeamReinforcementProposalCandidate(4, 12, M_PI * 12 ** 2 / 4, $area, $area, 0, 1),
        'stirrups' => crackStirrup(),
        'stresses' => app(BeamServiceStressVerificationCalculator::class)->calculate($moments, $geometry, $depth, app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37), app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B), $area, $profile),
        'concrete' => app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37),
        'steel' => app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B),
        'exposure' => $exposureClass,
        'profile' => $profile,
    ];
}

function calculateCracks(array $context, bool $withoutStirrups = false)
{
    return crackVerificationCalculator()->calculate($context['geometry'], $context['depth'], $context['longitudinal'], $withoutStirrups ? null : $context['stirrups'], $context['stresses'], $context['concrete'], $context['steel'], $context['exposure'], $context['profile']);
}

it('calculates the quasi-permanent direct crack-width verification from the real bar layout', function () {
    $result = calculateCracks(crackVerificationContext());

    expect($result->loadCombination)->toBe('QUASI_PERMANENT')
        ->and($result->loadDuration)->toBe(BeamCrackLoadDuration::LONG_TERM)
        ->and($result->coverToLongitudinalBar)->toBe(46.0)
        ->and(abs($result->barSpacing - 65.333333333333))->toBeLessThan(1e-12)
        ->and(abs($result->clearBarSpacing - 53.333333333333))->toBeLessThan(1e-12)
        ->and($result->effectiveTensionHeightFromDepth)->toBe(135.0)
        ->and(abs($result->effectiveTensionHeightFromNeutralAxis - 169.607380981476))->toBeLessThan(1e-9)
        ->and($result->effectiveTensionHeightFromHalfDepth)->toBe(300.0)
        ->and($result->effectiveTensionHeight)->toBe(135.0)
        ->and($result->effectiveTensionArea)->toBe(40500.0)
        ->and(abs($result->effectiveReinforcementRatio - 0.011170107212764))->toBeLessThan(1e-15)
        ->and($result->effectiveConcreteTensileStrength)->toBe(2.9)
        ->and(abs($result->steelStress - 238.867847171143))->toBeLessThan(1e-12)
        ->and($result->kt)->toBe(0.4)
        ->and($result->spacingFormulaCriterion)->toBe(BeamCrackSpacingFormulaCriterion::CLOSELY_SPACED_BARS)
        ->and(abs($result->maximumCrackSpacing - 339.03029719795))->toBeLessThan(1e-9)
        ->and(abs($result->strainDifferenceMain - 0.000639944718867))->toBeLessThan(1e-15)
        ->and(abs($result->strainDifferenceMinimum - 0.000716603541513))->toBeLessThan(1e-15)
        ->and($result->strainDifferenceCriterion)->toBe(BeamCrackStrainDifferenceCriterion::MINIMUM_STRAIN_DIFFERENCE)
        ->and(abs($result->crackWidth - 0.242950311652401))->toBeLessThan(1e-12)
        ->and($result->crackWidthLimit)->toBe(0.4)
        ->and(abs($result->utilization - 0.607375779131004))->toBeLessThan(1e-12)
        ->and($result->status)->toBe(BeamCrackVerificationStatus::COMPLIANT);
});

it('calculates cracking from the longitudinal layout when no stirrup proposal is supplied', function () {
    $result = calculateCracks(crackVerificationContext(), true);

    // c = 40 + 8 = 48 mm; s = [300 - 2(48 + 12/2)] / 3 = 64 mm.
    // The direct EC2 §7.3.4 result is independently evaluated from this layout.
    expect($result->transverseBarDiameter)->toBe(8.0)
        ->and($result->coverToLongitudinalBar)->toBe(48.0)
        ->and($result->barSpacing)->toBe(64.0)
        ->and(abs($result->maximumCrackSpacing - 345.83029719795))->toBeLessThan(1e-9)
        ->and(abs($result->crackWidth - 0.247823215734544))->toBeLessThan(1e-10)
        ->and($result->status)->toBe(BeamCrackVerificationStatus::COMPLIANT);
});

it('uses the wide-bar branch when the actual bar spacing exceeds its limit', function () {
    $result = calculateCracks(crackVerificationContext(1000));

    expect($result->spacingFormulaCriterion)->toBe(BeamCrackSpacingFormulaCriterion::WIDELY_SPACED_BARS)
        ->and(abs($result->maximumCrackSpacing - 1.3 * 3 * $result->effectiveTensionHeightFromNeutralAxis))->toBeLessThan(1e-9);
});

it('keeps kt configurable and refuses an exposure class with no validated crack-width limit', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();

    expect($profile->beamCrackWidthRequirements->ktFor(BeamCrackLoadDuration::SHORT_TERM))->toBe(0.6)
        ->and(fn () => calculateCracks(crackVerificationContext(exposureClass: ExposureClassCode::XC2)))
        ->toThrow(BeamCrackVerificationException::class, 'UNSUPPORTED_CRACK_WIDTH_EXPOSURE_CLASS');
});

it('reports a local crack-width exceedance without changing reinforcement', function () {
    $result = calculateCracks(crackVerificationContext(quasiMoment: 200));

    expect($result->crackWidth)->toBeGreaterThan($result->crackWidthLimit)
        ->and($result->status)->toBe(BeamCrackVerificationStatus::NOT_COMPLIANT);
});
