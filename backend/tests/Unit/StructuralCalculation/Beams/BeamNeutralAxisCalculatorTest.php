<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisException;
use App\StructuralCalculation\Beams\BeamNeutralAxisRejectionReason;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentResult;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteRectangularStressBlockException;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteRectangularStressBlockParametersCalculator;
use App\StructuralCalculation\Eurocode\Concrete\ConcreteRectangularStressBlockRejectionReason;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;

function beamNeutralAxisCalculator(): BeamNeutralAxisCalculator
{
    return app(BeamNeutralAxisCalculator::class);
}

function neutralAxisReducedMoment(float $muEd): BeamReducedMomentResult
{
    return new BeamReducedMomentResult(95.45859375, 95458593.75, 300, 544, 20, 1775616000, $muEd);
}

function neutralAxisDepth(float $depth = 544): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 16, LongitudinalBarDiameterSource::CONFIG, 56, $depth);
}

function neutralAxisConcrete(float $fck = 30): BeamFlexuralConcreteDesignStrength
{
    return new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, $fck, 1, 1.5, 20);
}

it('calculates lambda eta xi and x for the C30/37 integration reference', function () {
    $muEd = 0.053760832156277;
    $result = beamNeutralAxisCalculator()->calculate(neutralAxisReducedMoment($muEd), neutralAxisDepth(), neutralAxisConcrete());
    $expectedXi = (1 - sqrt(1 - 2 * $muEd)) / 0.8;

    expect($result->characteristicConcreteStrength)->toBe(30.0)
        ->and($result->lambda)->toBe(0.8)
        ->and($result->eta)->toBe(1.0)
        ->and(abs($result->neutralAxisRatio - $expectedXi))->toBeLessThan(0.000000001)
        ->and(abs($result->neutralAxisDepth - $expectedXi * 544))->toBeLessThan(0.000000001)
        ->and($result::LENGTH_UNIT)->toBe('mm')
        ->and($result::DIMENSIONLESS_UNIT)->toBe('dimensionless')
        ->and($result::RATIO_FORMULA)->toBe('ξ = [1 - sqrt(1 - 2 × μEd / η)] / λ');
});

it('uses the normal-strength parameters at the fck 50 MPa boundary', function () {
    $parameters = app(ConcreteRectangularStressBlockParametersCalculator::class)->calculate(50);

    expect($parameters->lambda)->toBe(0.8)
        ->and($parameters->eta)->toBe(1.0);
});

it('uses the high-strength EC2 expressions above fck 50 MPa', function () {
    $parameters = app(ConcreteRectangularStressBlockParametersCalculator::class)->calculate(60);
    $result = beamNeutralAxisCalculator()->calculate(neutralAxisReducedMoment(0.1), neutralAxisDepth(), neutralAxisConcrete(60));
    $expectedXi = (1 - sqrt(1 - 2 * 0.1 / 0.95)) / 0.775;

    expect($parameters->lambda)->toBe(0.775)
        ->and($parameters->eta)->toBe(0.95)
        ->and($result->lambda)->toBe(0.775)
        ->and($result->eta)->toBe(0.95)
        ->and(abs($result->neutralAxisRatio - $expectedXi))->toBeLessThan(0.000000001);
});

it('supports fck 90 MPa but rejects extrapolation beyond it', function () {
    $parameters = app(ConcreteRectangularStressBlockParametersCalculator::class)->calculate(90);

    expect(abs($parameters->lambda - 0.7))->toBeLessThan(0.000000001)
        ->and($parameters->eta)->toBe(0.8);

    try {
        app(ConcreteRectangularStressBlockParametersCalculator::class)->calculate(90.1);
    } catch (ConcreteRectangularStressBlockException $exception) {
        expect($exception->reason)->toBe(ConcreteRectangularStressBlockRejectionReason::UNSUPPORTED_CHARACTERISTIC_CONCRETE_STRENGTH);

        return;
    }

    throw new RuntimeException('Expected fck above 90 MPa to be rejected.');
});

it('keeps a zero reduced moment mathematically valid', function () {
    $result = beamNeutralAxisCalculator()->calculate(neutralAxisReducedMoment(0), neutralAxisDepth(), neutralAxisConcrete());

    expect($result->radicand)->toBe(1.0)
        ->and($result->neutralAxisRatio)->toBe(0.0)
        ->and($result->neutralAxisDepth)->toBe(0.0);
});

it('rejects a negative radicand and invalid effective depth', function (BeamReducedMomentResult $reducedMoment, BeamEffectiveDepthResult $depth, BeamNeutralAxisRejectionReason $reason) {
    try {
        beamNeutralAxisCalculator()->calculate($reducedMoment, $depth, neutralAxisConcrete());
    } catch (BeamNeutralAxisException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid neutral-axis input to be rejected.');
})->with([
    'negative reduced moment' => [neutralAxisReducedMoment(-0.1), neutralAxisDepth(), BeamNeutralAxisRejectionReason::INVALID_REDUCED_DESIGN_MOMENT],
    'negative radicand' => [neutralAxisReducedMoment(0.6), neutralAxisDepth(), BeamNeutralAxisRejectionReason::INVALID_NEUTRAL_AXIS_RADICAND],
    'non-positive depth' => [neutralAxisReducedMoment(0.1), neutralAxisDepth(0), BeamNeutralAxisRejectionReason::INVALID_EFFECTIVE_DEPTH],
]);

it('chains the real beam input through moment, depth, strengths, muEd and neutral axis', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'DESIGN',
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED',
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $selfWeight = app(SelfWeightCalculator::class)->calculate(
        $setup->geometry,
        $setup->permanentLoads->includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $ultimate = app(BeamUltimateCombinationCalculator::class)->calculate($actions, $profile);
    $serviceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($actions, $profile);
    $bending = app(SimplySupportedBeamBendingMomentCalculator::class)->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
    $cover = app(NominalCoverCalculator::class)->calculate(
        new CoverCalculationInput(
            coverMode: CoverMode::AUTO,
            exposureClasses: $setup->materials->exposureClasses,
            concreteClass: $setup->materials->concreteClass,
            designWorkingLifeYears: 50,
            reinforcementDiameter: BeamFlexuralDetailingAssumptions::mvp()->transverseReinforcementDiameter,
        ),
        $profile,
    );
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate(
        $setup->configuration->calculationMode,
        $setup->geometry,
        $cover,
        BeamFlexuralDetailingAssumptions::mvp(),
    );
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $reducedMoment = app(BeamReducedMomentCalculator::class)->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $result = beamNeutralAxisCalculator()->calculate($reducedMoment, $depth, $strengths->concrete);
    $expectedXi = (1 - sqrt(1 - 2 * $reducedMoment->reducedDesignMoment / 1.0)) / 0.8;

    expect($depth->effectiveDepth)->toBe(544.0)
        ->and($reducedMoment->reducedDesignMoment)->toBeGreaterThan(0)
        ->and($result->lambda)->toBe(0.8)
        ->and($result->eta)->toBe(1.0)
        ->and(abs($result->neutralAxisRatio - $expectedXi))->toBeLessThan(0.000000001)
        ->and(abs($result->neutralAxisDepth - $expectedXi * 544))->toBeLessThan(0.000000001);
});
