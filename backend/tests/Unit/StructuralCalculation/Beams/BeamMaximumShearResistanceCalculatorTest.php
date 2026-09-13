<?php

use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceCalculator;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceException;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceRejectionReason;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceStatus;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignResult;
use App\StructuralCalculation\Beams\BeamShearReinforcementGoverningRequirement;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;

function beamMaximumShearResistanceCalculator(): BeamMaximumShearResistanceCalculator
{
    return app(BeamMaximumShearResistanceCalculator::class);
}

function maximumShearReinforcementDesign(float $ved = 58.74375, float $leverArm = 531.019606207552, float $cotTheta = 2.5): BeamShearReinforcementDesignResult
{
    return new BeamShearReinforcementDesignResult(
        $ved, 63.862728452911, false, 300, $leverArm, 500, 500 / 1.15, $cotTheta,
        0.08 * sqrt(30) / 500, 0, 0.08 * sqrt(30) / 500 * 300,
        0.08 * sqrt(30) / 500 * 300, BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT, 151.748565285593,
    );
}

function maximumShearContext(float $ved = 58.74375, float $leverArm = 531.019606207552, float $cotTheta = 2.5): array
{
    return [
        'design' => maximumShearReinforcementDesign($ved, $leverArm, $cotTheta),
        'concrete' => app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37),
        'strength' => new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 1.5, 20),
        'profile' => app(FrenchEurocodeProfileRepository::class)->get(),
    ];
}

function calculateMaximumShear(array $context)
{
    return beamMaximumShearResistanceCalculator()->calculate($context['design'], $context['concrete'], $context['strength'], $context['profile']);
}

it('calculates VRd,max for the 4 HA12 chain using its actual lever arm', function () {
    $result = calculateMaximumShear(maximumShearContext());

    expect($result->leverArm)->toBe(531.019606207552)
        ->and($result->cotTheta)->toBe(2.5)
        ->and($result->tanTheta)->toBe(0.4)
        ->and($result->alphaCw)->toBe(1.0)
        ->and($result->concreteShearStrengthReductionFactor)->toBe(0.528)
        ->and(abs($result->maximumShearResistance - 580.093142229491))->toBeLessThan(1e-9)
        ->and(abs($result->utilizationMaximumShear - 0.101266065264))->toBeLessThan(1e-12)
        ->and($result->status)->toBe(BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK);
});

it('uses fcd in VRd,max and reduces nu1 for a higher concrete strength', function () {
    $context = maximumShearContext();
    $withLowerFcd = $context;
    $withLowerFcd['strength'] = new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 3, 10);
    $lowerFcdResult = calculateMaximumShear($withLowerFcd);
    $higherConcrete = new ConcreteProperties(ConcreteStrengthClass::C30_37, 60, 68, 4.1, 39000);
    $higherStrength = new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 60, 1, 1.5, 40);
    $higherContext = $context;
    $higherContext['concrete'] = $higherConcrete;
    $higherContext['strength'] = $higherStrength;
    $higherResult = calculateMaximumShear($higherContext);

    expect($lowerFcdResult->maximumShearResistance)->toBe($withLowerFcd['strength']->fcd / $context['strength']->fcd * calculateMaximumShear($context)->maximumShearResistance)
        ->and(abs($higherResult->concreteShearStrengthReductionFactor - 0.456))->toBeLessThan(1e-12)
        ->and($higherResult->concreteShearStrengthReductionFactor)->toBeLessThan(calculateMaximumShear($context)->concreteShearStrengthReductionFactor);
});

it('reports a maximum shear resistance exceedance without changing the reinforcement design', function () {
    $context = maximumShearContext(700);
    $result = calculateMaximumShear($context);

    expect($result->maximumShearResistance)->toBeGreaterThan(0)
        ->and($result->designShearForce)->toBeGreaterThan($result->maximumShearResistance)
        ->and($result->status)->toBe(BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED)
        ->and($context['design']->targetShearReinforcementPerLength)->toBe(0.08 * sqrt(30) / 500 * 300);
});

it('rejects a cotangent outside the profile range', function (float $cotTheta) {
    try {
        calculateMaximumShear(maximumShearContext(cotTheta: $cotTheta));
    } catch (BeamMaximumShearResistanceException $exception) {
        expect($exception->reason)->toBe(BeamMaximumShearResistanceRejectionReason::COT_THETA_OUTSIDE_PROFILE_RANGE);

        return;
    }

    throw new RuntimeException('Expected cotangent outside the profile range to be rejected.');
})->with([0.99, 2.51]);
