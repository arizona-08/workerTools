<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceCalculator;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceException;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceGoverningCriterion;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceRejectionReason;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceStatus;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamShearForce;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;

function beamConcreteShearResistanceCalculator(): BeamConcreteShearResistanceCalculator
{
    return app(BeamConcreteShearResistanceCalculator::class);
}

function concreteShearForce(float $value): BeamShearForce
{
    return new BeamShearForce($value, $value, $value, -$value, FundamentalUltimateCombinationExpression::EN1990_6_10, 'VEd');
}

function concreteShearEffectiveDepth(float $depth): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 600 - $depth, $depth);
}

function concreteShearContext(float $depth = 546, float $asl = 452.3893421169302, float $ved = 58.74375): array
{
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37);
    $designStrength = new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 1.5, 20);

    return [
        'force' => concreteShearForce($ved),
        'configuration' => BeamCalculationConfiguration::supported(),
        'geometry' => new BeamGeometry(6500, 300, 600),
        'depth' => concreteShearEffectiveDepth($depth),
        'concrete' => $concrete,
        'designStrength' => $designStrength,
        'asl' => $asl,
        'profile' => app(FrenchEurocodeProfileRepository::class)->get(),
    ];
}

function calculateConcreteShear(array $context)
{
    return beamConcreteShearResistanceCalculator()->calculate(
        $context['force'], $context['configuration'], $context['geometry'], $context['depth'],
        $context['concrete'], $context['designStrength'], $context['asl'], $context['profile'],
    );
}

it('calculates VRd,c for the 4 HA12 integration fixture from VEd and the actual candidate Asl', function () {
    $result = calculateConcreteShear(concreteShearContext());

    expect($result->designShearForce)->toBe(58.74375)
        ->and($result->webWidth)->toBe(300.0)
        ->and($result->effectiveDepth)->toBe(546.0)
        ->and(abs($result->sizeEffectFactor - 1.6052275326688025))->toBeLessThan(1e-12)
        ->and(abs($result->longitudinalReinforcementRatioRaw - 0.00276183969546221))->toBeLessThan(1e-12)
        ->and($result->longitudinalReinforcementRatioUsed)->toBe($result->longitudinalReinforcementRatioRaw)
        ->and($result->normalForce)->toBe(0.0)
        ->and($result->meanCompressiveStress)->toBe(0.0)
        ->and(abs($result->mainShearResistanceStress - 0.389784369867557))->toBeLessThan(1e-12)
        ->and(abs($result->minimumShearResistanceStress - 0.389882347087367))->toBeLessThan(1e-12)
        ->and($result->governingCriterion)->toBe(BeamConcreteShearResistanceGoverningCriterion::MINIMUM_SHEAR_RESISTANCE)
        ->and(abs($result->concreteShearResistance - 63.862728452911))->toBeLessThan(1e-9)
        ->and($result->utilizationConcreteShear)->toBeLessThan(1.0)
        ->and($result->status)->toBe(BeamConcreteShearResistanceStatus::SHEAR_REINFORCEMENT_NOT_REQUIRED_BY_VRDC_CHECK);
});

it('caps k and the longitudinal reinforcement ratio while preserving their raw values', function () {
    $result = calculateConcreteShear(concreteShearContext(depth: 50, asl: 10000));

    expect($result->sizeEffectFactorRaw)->toBe(3.0)
        ->and($result->sizeEffectFactor)->toBe(2.0)
        ->and($result->sizeEffectFactorCapped)->toBeTrue()
        ->and($result->longitudinalReinforcementRatioRaw)->toBeGreaterThan(0.02)
        ->and($result->longitudinalReinforcementRatioUsed)->toBe(0.02)
        ->and($result->longitudinalReinforcementRatioCapped)->toBeTrue();
});

it('can have the main expression govern and uses fck rather than fcd in it', function () {
    $context = concreteShearContext(asl: 1000);
    $result = calculateConcreteShear($context);
    $otherFcd = new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 3, 10);
    $withOtherFcd = beamConcreteShearResistanceCalculator()->calculate(
        $context['force'], $context['configuration'], $context['geometry'], $context['depth'],
        $context['concrete'], $otherFcd, $context['asl'], $context['profile'],
    );

    expect($result->governingCriterion)->toBe(BeamConcreteShearResistanceGoverningCriterion::MAIN_EXPRESSION)
        ->and($result->mainShearResistanceStress)->toBeGreaterThan($result->minimumShearResistanceStress)
        ->and($withOtherFcd->mainShearResistanceStress)->toBe($result->mainShearResistanceStress);
});

it('uses the supplied Asl rather than a flexural required area and converts the resistance to kN', function () {
    $context = concreteShearContext(asl: 100);
    $result = calculateConcreteShear($context);

    expect($result->longitudinalReinforcementArea)->toBe(100.0)
        ->and($result->longitudinalReinforcementRatioRaw)->toBe(100 / (300 * 546))
        ->and($result->concreteShearResistance)->toBe($result->governingResistanceStress * 300 * 546 / 1000);
});

it('keeps the V1 normal force fixed at zero', function () {
    $context = concreteShearContext();

    expect(fn () => beamConcreteShearResistanceCalculator()->calculate(
        $context['force'], $context['configuration'], $context['geometry'], $context['depth'],
        $context['concrete'], $context['designStrength'], $context['asl'], $context['profile'], 1,
    ))->toThrow(BeamConcreteShearResistanceException::class);

    try {
        beamConcreteShearResistanceCalculator()->calculate(
            $context['force'], $context['configuration'], $context['geometry'], $context['depth'],
            $context['concrete'], $context['designStrength'], $context['asl'], $context['profile'], 1,
        );
    } catch (BeamConcreteShearResistanceException $exception) {
        expect($exception->reason)->toBe(BeamConcreteShearResistanceRejectionReason::UNSUPPORTED_NORMAL_FORCE);
    }
});
