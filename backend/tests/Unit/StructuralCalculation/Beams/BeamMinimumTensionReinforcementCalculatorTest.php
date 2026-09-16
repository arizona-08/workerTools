<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementException;
use App\StructuralCalculation\Beams\BeamMinimumTensionReinforcementRejectionReason;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Beams\MinimumTensionReinforcementGoverningCriterion;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Beams\BeamLongitudinalReinforcementRequirements;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\SteelDuctilityClass;

function beamMinimumTensionReinforcementCalculator(): BeamMinimumTensionReinforcementCalculator
{
    return app(BeamMinimumTensionReinforcementCalculator::class);
}

function minimumReinforcementConcrete(float $fctm = 2.9): ConcreteProperties
{
    return new ConcreteProperties(ConcreteStrengthClass::C30_37, 30, 38, $fctm, 33000);
}

function minimumReinforcementSteel(float $fyk = 500): ReinforcementSteelProperties
{
    return new ReinforcementSteelProperties(ReinforcementSteelGrade::B500B, $fyk, 200000, SteelDuctilityClass::B);
}

function minimumReinforcementGeometry(float $width = 300, float $height = 600): BeamGeometry
{
    return new BeamGeometry(6500, $width, $height);
}

function minimumReinforcementDepth(float $depth = 544): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 40, 8, 16, LongitudinalBarDiameterSource::CONFIG, 56, $depth);
}

function frenchMinimumReinforcementRequirements(): BeamLongitudinalReinforcementRequirements
{
    return app(FrenchEurocodeProfileRepository::class)->get()->beamLongitudinalReinforcementRequirements;
}

it('calculates both EC2 minimum-reinforcement terms for C30/37 and B500B', function () {
    $result = beamMinimumTensionReinforcementCalculator()->calculate(
        minimumReinforcementConcrete(),
        minimumReinforcementSteel(),
        minimumReinforcementGeometry(),
        minimumReinforcementDepth(),
        frenchMinimumReinforcementRequirements(),
    );

    expect($result->concreteClass)->toBe(ConcreteStrengthClass::C30_37)
        ->and($result->meanTensileConcreteStrength)->toBe(2.9)
        ->and($result->steelGrade)->toBe(ReinforcementSteelGrade::B500B)
        ->and($result->characteristicSteelStrength)->toBe(500.0)
        ->and($result->tensionZoneMeanWidth)->toBe(300.0)
        ->and($result->effectiveDepth)->toBe(544.0)
        ->and($result->strengthBasedMinimum)->toBe(246.1056)
        ->and($result->absoluteMinimum)->toBe(212.16)
        ->and($result->requiredMinimum)->toBe(246.1056)
        ->and($result->governingCriterion)->toBe(MinimumTensionReinforcementGoverningCriterion::FCTM_FYK)
        ->and($result::AREA_UNIT)->toBe('mm²');
});

it('selects the absolute-ratio term when it is greater than the fctm-fyk term', function () {
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C20_25);
    $steel = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);
    $result = beamMinimumTensionReinforcementCalculator()->calculate(
        $concrete,
        $steel,
        minimumReinforcementGeometry(),
        minimumReinforcementDepth(),
        frenchMinimumReinforcementRequirements(),
    );

    expect(abs($result->strengthBasedMinimum - 186.7008))->toBeLessThan(0.000000001)
        ->and($result->absoluteMinimum)->toBe(212.16)
        ->and($result->requiredMinimum)->toBe(212.16)
        ->and($result->governingCriterion)->toBe(MinimumTensionReinforcementGoverningCriterion::ABSOLUTE_RATIO);
});

it('uses fyk rather than fyd and uses bt times d rather than b times h', function () {
    $result = beamMinimumTensionReinforcementCalculator()->calculate(
        minimumReinforcementConcrete(),
        minimumReinforcementSteel(),
        minimumReinforcementGeometry(width: 250, height: 900),
        minimumReinforcementDepth(500),
        frenchMinimumReinforcementRequirements(),
    );
    $expectedUsingFyk = 0.26 * (2.9 / 500) * 250 * 500;
    $incorrectUsingFyd = 0.26 * (2.9 / (500 / 1.15)) * 250 * 500;

    expect($result->tensionZoneMeanWidth)->toBe(250.0)
        ->and($result->effectiveDepth)->toBe(500.0)
        ->and($result->strengthBasedMinimum)->toBe($expectedUsingFyk)
        ->and($result->strengthBasedMinimum)->not->toBe($incorrectUsingFyd)
        ->and($result->absoluteMinimum)->toBe(162.5)
        ->and($result->strengthBasedMinimum)->not->toBe(0.26 * (2.9 / 500) * 250 * 900);
});

it('rejects invalid material geometry and normative-requirement inputs', function (
    ConcreteProperties $concrete,
    ReinforcementSteelProperties $steel,
    BeamGeometry $geometry,
    BeamEffectiveDepthResult $depth,
    BeamLongitudinalReinforcementRequirements $requirements,
    BeamMinimumTensionReinforcementRejectionReason $reason,
) {
    try {
        beamMinimumTensionReinforcementCalculator()->calculate($concrete, $steel, $geometry, $depth, $requirements);
    } catch (BeamMinimumTensionReinforcementException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid minimum-reinforcement input to be rejected.');
})->with([
    'non-positive fctm' => [minimumReinforcementConcrete(0), minimumReinforcementSteel(), minimumReinforcementGeometry(), minimumReinforcementDepth(), frenchMinimumReinforcementRequirements(), BeamMinimumTensionReinforcementRejectionReason::INVALID_MEAN_TENSILE_CONCRETE_STRENGTH],
    'non-positive fyk' => [minimumReinforcementConcrete(), minimumReinforcementSteel(0), minimumReinforcementGeometry(), minimumReinforcementDepth(), frenchMinimumReinforcementRequirements(), BeamMinimumTensionReinforcementRejectionReason::INVALID_CHARACTERISTIC_STEEL_STRENGTH],
    'non-positive bt' => [minimumReinforcementConcrete(), minimumReinforcementSteel(), minimumReinforcementGeometry(0), minimumReinforcementDepth(), frenchMinimumReinforcementRequirements(), BeamMinimumTensionReinforcementRejectionReason::INVALID_TENSION_ZONE_MEAN_WIDTH],
    'non-positive d' => [minimumReinforcementConcrete(), minimumReinforcementSteel(), minimumReinforcementGeometry(), minimumReinforcementDepth(0), frenchMinimumReinforcementRequirements(), BeamMinimumTensionReinforcementRejectionReason::INVALID_EFFECTIVE_DEPTH],
    'non-positive strength coefficient' => [minimumReinforcementConcrete(), minimumReinforcementSteel(), minimumReinforcementGeometry(), minimumReinforcementDepth(), new BeamLongitudinalReinforcementRequirements(0, 0.0013), BeamMinimumTensionReinforcementRejectionReason::INVALID_STRENGTH_COEFFICIENT],
    'non-positive absolute ratio' => [minimumReinforcementConcrete(), minimumReinforcementSteel(), minimumReinforcementGeometry(), minimumReinforcementDepth(), new BeamLongitudinalReinforcementRequirements(0.26, 0), BeamMinimumTensionReinforcementRejectionReason::INVALID_MINIMUM_REINFORCEMENT_RATIO],
]);

it('chains beam input through As_req and As_min without combining their values', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $selfWeight = app(SelfWeightCalculator::class)->calculate($setup->geometry, true, app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete());
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $ultimate = app(BeamUltimateCombinationCalculator::class)->calculate($actions, $profile);
    $serviceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($actions, $profile);
    $bending = app(SimplySupportedBeamBendingMomentCalculator::class)->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);
    $cover = app(NominalCoverCalculator::class)->calculate(new CoverCalculationInput(CoverMode::AUTO, $setup->materials->exposureClasses, $setup->materials->concreteClass, 50, BeamFlexuralDetailingAssumptions::supported()->transverseReinforcementDiameter), $profile);
    $depth = app(BeamEffectiveDepthCalculator::class)->calculate($setup->configuration->calculationMode, $setup->geometry, $cover, BeamFlexuralDetailingAssumptions::supported());
    $strengths = app(BeamFlexuralDesignStrengthsCalculator::class)->calculate($setup->materials, $profile);
    $reducedMoment = app(BeamReducedMomentCalculator::class)->calculate($bending->ultimate, $setup->geometry, $depth, $strengths->concrete);
    $neutralAxis = app(BeamNeutralAxisCalculator::class)->calculate($reducedMoment, $depth, $strengths->concrete);
    $leverArm = app(BeamLeverArmCalculator::class)->calculate($depth, $neutralAxis);
    $required = app(BeamRequiredTensionReinforcementCalculator::class)->calculate($bending->ultimate, $strengths->steel, $leverArm);
    $minimum = beamMinimumTensionReinforcementCalculator()->calculate(
        app(ConcreteClassRepository::class)->get($setup->materials->concreteClass),
        app(ReinforcementSteelGradeRepository::class)->get($setup->materials->steelGrade),
        $setup->geometry,
        $depth,
        $profile->beamLongitudinalReinforcementRequirements,
    );

    expect(abs($required->requiredReinforcementArea - 415.06771776287212))->toBeLessThan(0.000001)
        ->and($minimum->requiredMinimum)->toBe(246.1056)
        ->and($required->requiredReinforcementArea)->toBeGreaterThan($minimum->requiredMinimum)
        ->and(property_exists($minimum, 'designReinforcementArea'))->toBeFalse();
});
