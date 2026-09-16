<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamConcreteShearResistanceCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLeverArmResult;
use App\StructuralCalculation\Beams\BeamShearDesignAssumptions;
use App\StructuralCalculation\Beams\BeamShearForce;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignCalculator;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignException;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignRejectionReason;
use App\StructuralCalculation\Beams\BeamShearReinforcementGoverningRequirement;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

function beamShearReinforcementDesignCalculator(): BeamShearReinforcementDesignCalculator
{
    return app(BeamShearReinforcementDesignCalculator::class);
}

function stirrupDesignContext(float $ved = 58.74375, float $leverArm = 531.019606207552): array
{
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37);
    $geometry = new BeamGeometry(6500, 300, 600);
    $effectiveDepth = new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546);
    $force = new BeamShearForce($ved, $ved, $ved, -$ved, FundamentalUltimateCombinationExpression::EN1990_6_10, 'VEd');
    $concreteShear = app(BeamConcreteShearResistanceCalculator::class)->calculate(
        $force,
        BeamCalculationConfiguration::supported(),
        $geometry,
        $effectiveDepth,
        $concrete,
        new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 1.5, 20),
        4 * M_PI * 12 ** 2 / 4,
        $profile,
    );

    return [
        'concreteShear' => $concreteShear,
        'leverArm' => new BeamLeverArmResult(546, 37.450984481121, 0.068591546669, 0.8, 29.960787584897, 14.980393792448, $leverArm),
        'concrete' => $concrete,
        'steel' => app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B),
        'profile' => $profile,
    ];
}

function calculateStirrupDesign(array $context, ?BeamShearDesignAssumptions $assumptions = null)
{
    return beamShearReinforcementDesignCalculator()->calculate(
        $context['concreteShear'], $context['leverArm'], $context['concrete'], $context['steel'],
        $context['profile'], $assumptions ?? BeamShearDesignAssumptions::supported(),
    );
}

it('keeps a minimum transverse reinforcement target when the current VEd is below VRd,c', function () {
    $result = calculateStirrupDesign(stirrupDesignContext());
    $minimumRatio = 0.08 * sqrt(30) / 500;
    $minimum = $minimumRatio * 300;

    expect($result->requiredByShearDemand)->toBeFalse()
        ->and($result->requiredShearReinforcementPerLength)->toBe(0.0)
        ->and(abs($result->minimumShearReinforcementRatio - $minimumRatio))->toBeLessThan(1e-12)
        ->and(abs($result->minimumShearReinforcementPerLength - $minimum))->toBeLessThan(1e-12)
        ->and($result->targetShearReinforcementPerLength)->toBe($result->minimumShearReinforcementPerLength)
        ->and($result->governingRequirement)->toBe(BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT)
        ->and($result->cotTheta)->toBe(2.5)
        ->and($result->leverArm)->toBe(531.019606207552);
});

it('uses VEd, not VEd minus VRd,c, when shear demand governs', function () {
    $context = stirrupDesignContext(500);
    $result = calculateStirrupDesign($context);
    $denominator = 531.019606207552 * (500 / 1.15) * 2.5;
    $expected = 500000 / $denominator;
    $incorrectResidualDemand = (500 - $context['concreteShear']->concreteShearResistance) * 1000 / $denominator;

    expect($result->requiredByShearDemand)->toBeTrue()
        ->and(abs($result->requiredShearReinforcementPerLength - $expected))->toBeLessThan(1e-12)
        ->and($result->requiredShearReinforcementPerLength)->toBeGreaterThan($result->minimumShearReinforcementPerLength)
        ->and($result->targetShearReinforcementPerLength)->toBe($result->requiredShearReinforcementPerLength)
        ->and($result->governingRequirement)->toBe(BeamShearReinforcementGoverningRequirement::SHEAR_DEMAND)
        ->and($result->requiredShearReinforcementPerLength)->not->toBe($incorrectResidualDemand);
});

it('lets the minimum govern even when the concrete shear check requires reinforcement', function () {
    $result = calculateStirrupDesign(stirrupDesignContext(70));

    expect($result->requiredByShearDemand)->toBeTrue()
        ->and($result->requiredShearReinforcementPerLength)->toBeGreaterThan(0)
        ->and($result->requiredShearReinforcementPerLength)->toBeLessThan($result->minimumShearReinforcementPerLength)
        ->and($result->targetShearReinforcementPerLength)->toBe($result->minimumShearReinforcementPerLength)
        ->and($result->governingRequirement)->toBe(BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT);
});

it('uses fyk for the minimum and fywd for the trellis resistance', function () {
    $result = calculateStirrupDesign(stirrupDesignContext(500));

    expect(abs($result->minimumShearReinforcementRatio - (0.08 * sqrt(30) / 500)))->toBeLessThan(1e-12)
        ->and(abs($result->stirrupSteelDesignStrength - 500 / 1.15))->toBeLessThan(1e-12)
        ->and(abs($result->targetShearResistance - $result->targetShearReinforcementPerLength * $result->leverArm * $result->stirrupSteelDesignStrength * $result->cotTheta / 1000))->toBeLessThan(1e-12);
});

it('rejects a design cotangent outside the profile range', function (float $cotTheta) {
    $context = stirrupDesignContext();

    try {
        calculateStirrupDesign($context, new BeamShearDesignAssumptions($cotTheta));
    } catch (BeamShearReinforcementDesignException $exception) {
        expect($exception->reason)->toBe(BeamShearReinforcementDesignRejectionReason::DESIGN_COT_THETA_OUTSIDE_PROFILE_RANGE);

        return;
    }

    throw new RuntimeException('Expected cotangent outside the profile range to be rejected.');
})->with([0.99, 2.51]);
