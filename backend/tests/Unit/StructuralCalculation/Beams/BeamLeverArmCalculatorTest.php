<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamLeverArmException;
use App\StructuralCalculation\Beams\BeamLeverArmRejectionReason;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamNeutralAxisResult;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;

function beamLeverArmCalculator(): BeamLeverArmCalculator
{
    return app(BeamLeverArmCalculator::class);
}

function leverArmDepth(float $depth = 544): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 16, LongitudinalBarDiameterSource::CONFIG, 56, $depth);
}

function leverArmNeutralAxis(float $depth = 544, float $neutralAxisDepth = 37.596713565478, float $lambda = 0.8): BeamNeutralAxisResult
{
    return new BeamNeutralAxisResult(
        0.053760832156277,
        30,
        $lambda,
        1,
        0.892478335687446,
        $depth === 0.0 ? 0.0 : $neutralAxisDepth / $depth,
        $depth,
        $neutralAxisDepth,
    );
}

it('calculates the reference lever arm from d lambda and x', function () {
    $result = beamLeverArmCalculator()->calculate(leverArmDepth(), leverArmNeutralAxis());
    $expectedCompressionBlockDepth = 0.8 * 37.596713565478;
    $expectedLeverArm = 544 - $expectedCompressionBlockDepth / 2;

    expect(abs($result->compressionBlockDepth - $expectedCompressionBlockDepth))->toBeLessThan(0.000000001)
        ->and(abs($result->compressionResultantDepth - $expectedCompressionBlockDepth / 2))->toBeLessThan(0.000000001)
        ->and(abs($result->leverArm - $expectedLeverArm))->toBeLessThan(0.000000001)
        ->and($result::UNIT)->toBe('mm')
        ->and($result::FORMULA)->toBe('z = d - λ × x / 2');
});

it('keeps the x and xi forms of the lever arm equivalent', function () {
    $result = beamLeverArmCalculator()->calculate(leverArmDepth(), leverArmNeutralAxis());
    $equivalentLeverArm = $result->effectiveDepth * (1 - $result->lambda * $result->neutralAxisRatio / 2);

    expect(abs($result->leverArm - $equivalentLeverArm))->toBeLessThan(0.000000001)
        ->and($result::EQUIVALENT_FORMULA)->toBe('z = d × (1 - λ × ξ / 2)');
});

it('keeps the zero neutral-axis case valid with z equal to d', function () {
    $result = beamLeverArmCalculator()->calculate(leverArmDepth(), leverArmNeutralAxis(neutralAxisDepth: 0));

    expect($result->compressionBlockDepth)->toBe(0.0)
        ->and($result->compressionResultantDepth)->toBe(0.0)
        ->and($result->leverArm)->toBe(544.0);
});

it('rejects incoherent lever-arm geometry', function () {
    $cases = [
        [leverArmDepth(0), leverArmNeutralAxis(0, 0), BeamLeverArmRejectionReason::INVALID_EFFECTIVE_DEPTH],
        [leverArmDepth(), leverArmNeutralAxis(neutralAxisDepth: -1), BeamLeverArmRejectionReason::INVALID_NEUTRAL_AXIS_DEPTH],
        [leverArmDepth(), leverArmNeutralAxis(neutralAxisDepth: 545), BeamLeverArmRejectionReason::NEUTRAL_AXIS_BEYOND_EFFECTIVE_DEPTH],
        [leverArmDepth(), leverArmNeutralAxis(lambda: 0), BeamLeverArmRejectionReason::INVALID_STRESS_BLOCK_LAMBDA],
        [leverArmDepth(), leverArmNeutralAxis(neutralAxisDepth: 544, lambda: 3), BeamLeverArmRejectionReason::NON_POSITIVE_LEVER_ARM],
    ];

    foreach ($cases as [$depth, $neutralAxis, $reason]) {
        try {
            beamLeverArmCalculator()->calculate($depth, $neutralAxis);
        } catch (BeamLeverArmException $exception) {
            expect($exception->reason)->toBe($reason);

            continue;
        }

        throw new RuntimeException('Expected incoherent lever-arm geometry to be rejected.');
    }
});

it('chains the real beam input through neutral axis into the lever arm', function () {
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
            reinforcementDiameter: BeamFlexuralDetailingAssumptions::supported()->transverseReinforcementDiameter,
        ),
        $profile,
    );
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate(
        $setup->configuration->calculationMode,
        $setup->geometry,
        $cover,
        BeamFlexuralDetailingAssumptions::supported(),
    );
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $reducedMoment = app(BeamReducedMomentCalculator::class)->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $neutralAxis = app(BeamNeutralAxisCalculator::class)->calculate($reducedMoment, $depth, $strengths->concrete);
    $result = beamLeverArmCalculator()->calculate($depth, $neutralAxis);
    $expectedLeverArm = $depth->effectiveDepth - $neutralAxis->lambda * $neutralAxis->neutralAxisDepth / 2;

    expect($depth->effectiveDepth)->toBe(544.0)
        ->and($neutralAxis->lambda)->toBe(0.8)
        ->and(abs($result->leverArm - $expectedLeverArm))->toBeLessThan(0.000000001)
        ->and($result->leverArm)->toBeGreaterThan(0)
        ->and($result->leverArm)->not->toBe(0.95 * $depth->effectiveDepth);
});
