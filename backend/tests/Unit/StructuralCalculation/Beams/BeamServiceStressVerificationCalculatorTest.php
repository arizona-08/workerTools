<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamBendingMomentResult;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamServiceStressCheckStatus;
use App\StructuralCalculation\Beams\BeamServiceStressVerificationCalculator;
use App\StructuralCalculation\Beams\BeamServiceStressVerificationException;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\BeamTensionFace;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

function beamServiceStressVerificationCalculator(): BeamServiceStressVerificationCalculator
{
    return app(BeamServiceStressVerificationCalculator::class);
}
function serviceMoment(float $value, FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $reference): BeamBendingMoment
{
    return new BeamBendingMoment(0, $value, $reference, 'M');
}
function serviceStressContext(float $characteristic = 68.65625, float $quasi = 55.7171875): array
{
    $ref = FundamentalUltimateCombinationExpression::EN1990_6_10;

    return [
        'moments' => new BeamBendingMomentResult(6.5, BeamSupportSystem::SIMPLY_SUPPORTED, BeamLoadModel::UNIFORMLY_DISTRIBUTED, .125, 3.25, serviceMoment(0, $ref), serviceMoment($characteristic, ServiceabilityCombinationExpression::EN1990_6_14), serviceMoment(0, ServiceabilityCombinationExpression::EN1990_6_15), serviceMoment($quasi, ServiceabilityCombinationExpression::EN1990_6_16)),
        'geometry' => new BeamGeometry(6500, 300, 600),
        'depth' => new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546),
        'concrete' => app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37),
        'steel' => app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B),
        'profile' => app(FrenchEurocodeProfileRepository::class)->get(),
    ];
}
function calculateServiceStress(array $c, float $tensionArea = 4 * M_PI * 12 ** 2 / 4, float $compressedArea = 0.0)
{
    return beamServiceStressVerificationCalculator()->calculate($c['moments'], $c['geometry'], $c['depth'], $c['concrete'], $c['steel'], $tensionArea, $c['profile'], $compressedArea);
}

it('verifies the cracked elastic instantaneous reference section', function () {
    $r = calculateServiceStress(serviceStressContext());
    expect(abs($r->modularRatio - 200000 / 33000))->toBeLessThan(1e-12)
        ->and(abs($r->crackedNeutralAxisDepth - 91.177857055571))->toBeLessThan(1e-9)
        ->and(abs($r->crackedSecondMomentOfArea - 642967685.23732))->toBeLessThan(1e-4)
        ->and(abs($r->concreteCharacteristic->stress - 9.735994346529))->toBeLessThan(1e-12)
        ->and(abs($r->steelCharacteristic->stress - 294.339527319892))->toBeLessThan(1e-12)
        ->and(abs($r->concreteQuasiPermanent->stress - 7.90113387353))->toBeLessThan(1e-12)
        ->and(abs($r->concreteCharacteristic->limit - 18))->toBeLessThan(1e-12)
        ->and(abs($r->steelCharacteristic->limit - 400))->toBeLessThan(1e-12)
        ->and(abs($r->concreteQuasiPermanent->limit - 13.5))->toBeLessThan(1e-12)
        ->and($r->concreteCharacteristic->status)->toBe(BeamServiceStressCheckStatus::COMPLIANT)
        ->and($r->steelCharacteristic->status)->toBe(BeamServiceStressCheckStatus::COMPLIANT)
        ->and($r->concreteQuasiPermanent->status)->toBe(BeamServiceStressCheckStatus::COMPLIANT)
        ->and($r->steelQuasiPermanent->status)->toBe(BeamServiceStressCheckStatus::NOT_APPLICABLE)
        ->and($r->frequent->status)->toBe(BeamServiceStressCheckStatus::NOT_APPLICABLE)
        ->and($r->steelCharacteristic->moment)->toBe(68.65625)
        ->and($r->steelCharacteristic->method)->toBe('CRACKED_ELASTIC')
        ->and($r->status)->toBe(BeamServiceStressCheckStatus::COMPLIANT);
});

it('reports local concrete and steel stress exceedance and can remain explicitly not checked', function () {
    $r = calculateServiceStress(serviceStressContext(200, 180));
    expect($r->status)->toBe(BeamServiceStressCheckStatus::NOT_COMPLIANT)
        ->and($r->concreteCharacteristic->status)->toBe(BeamServiceStressCheckStatus::NOT_COMPLIANT)
        ->and($r->steelCharacteristic->status)->toBe(BeamServiceStressCheckStatus::NOT_COMPLIANT)
        ->and(beamServiceStressVerificationCalculator()->notChecked()->status)->toBe(BeamServiceStressCheckStatus::NOT_CHECKED);
});

it('reuses the cracked-section stress engine for signed cantilever moments and top tension steel', function () {
    $context = serviceStressContext();
    $reference = FundamentalUltimateCombinationExpression::EN1990_6_10;
    $cantileverMoments = new BeamBendingMomentResult(
        4,
        BeamSupportSystem::CANTILEVER,
        BeamLoadModel::UNIFORMLY_DISTRIBUTED,
        .5,
        0,
        serviceMoment(0, $reference),
        serviceMoment(-68.65625, ServiceabilityCombinationExpression::EN1990_6_14),
        serviceMoment(-60, ServiceabilityCombinationExpression::EN1990_6_15),
        serviceMoment(-55.7171875, ServiceabilityCombinationExpression::EN1990_6_16),
    );
    $topDepth = new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546, BeamTensionFace::TOP);

    $result = beamServiceStressVerificationCalculator()->calculate($cantileverMoments, $context['geometry'], $topDepth, $context['concrete'], $context['steel'], 4 * M_PI * 12 ** 2 / 4, $context['profile']);

    expect($result->tensionFace)->toBe(BeamTensionFace::TOP)
        ->and(abs($result->concreteCharacteristic->stress - 9.735994346529))->toBeLessThan(1e-12)
        ->and($result->concreteCharacteristic->moment)->toBe(-68.65625)
        ->and($result->status)->toBe(BeamServiceStressCheckStatus::COMPLIANT);
});

it('rejects invalid section data and unsupported compressed reinforcement explicitly', function () {
    $context = serviceStressContext();
    $context['geometry'] = new BeamGeometry(6500, 0, 600);

    expect(fn () => calculateServiceStress($context))
        ->toThrow(BeamServiceStressVerificationException::class, 'INVALID_SECTION_WIDTH');
    expect(fn () => calculateServiceStress(serviceStressContext(), tensionArea: 0))
        ->toThrow(BeamServiceStressVerificationException::class, 'INVALID_TENSION_REINFORCEMENT_AREA');
    expect(fn () => calculateServiceStress(serviceStressContext(), compressedArea: 100))
        ->toThrow(BeamServiceStressVerificationException::class, 'UNSUPPORTED_COMPRESSED_REINFORCEMENT_SLS');
});
