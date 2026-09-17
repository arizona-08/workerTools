<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDesignStrengthsCalculator;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamFlexuralSteelDesignStrength;
use App\StructuralCalculation\Beams\BeamLeverArmCalculator;
use App\StructuralCalculation\Beams\BeamLeverArmResult;
use App\StructuralCalculation\Beams\BeamNeutralAxisCalculator;
use App\StructuralCalculation\Beams\BeamReducedMomentCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementCalculator;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementException;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementRejectionReason;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

function beamRequiredTensionReinforcementCalculator(): BeamRequiredTensionReinforcementCalculator
{
    return app(BeamRequiredTensionReinforcementCalculator::class);
}

function requiredTensionReinforcementMoment(float $designMoment = 95.45859375): BeamBendingMoment
{
    return new BeamBendingMoment(18.075, $designMoment, FundamentalUltimateCombinationExpression::EN1990_6_10, 'Mmax = w × l_eff² / 8');
}

function requiredTensionReinforcementSteel(float $fyd = 500 / 1.15): BeamFlexuralSteelDesignStrength
{
    return new BeamFlexuralSteelDesignStrength(ReinforcementSteelGrade::B500B, 500, 1.15, $fyd);
}

function requiredTensionReinforcementLeverArm(float $leverArm = 528.96131457381): BeamLeverArmResult
{
    return new BeamLeverArmResult(544, 37.596713565478, 37.596713565478 / 544, 0.8, 30.0773708523824, 15.0386854261912, $leverArm);
}

it('calculates the required tension reinforcement with coherent N and mm units', function () {
    $result = beamRequiredTensionReinforcementCalculator()->calculate(
        requiredTensionReinforcementMoment(),
        requiredTensionReinforcementSteel(),
        requiredTensionReinforcementLeverArm(),
    );
    $expectedProduct = (500 / 1.15) * 528.96131457381;
    $expectedArea = 95.45859375 * 1_000_000 / $expectedProduct;

    expect($result->designMoment)->toBe(95.45859375)
        ->and($result->designMomentInNewtonMillimetres)->toBe(95458593.75)
        ->and(abs($result->steelDesignStrength - 500 / 1.15))->toBeLessThan(0.000000001)
        ->and($result->leverArm)->toBe(528.96131457381)
        ->and(abs($result->steelLeverArmProduct - $expectedProduct))->toBeLessThan(0.000000001)
        ->and(abs($result->requiredReinforcementArea - $expectedArea))->toBeLessThan(0.000000001)
        ->and(abs($result->requiredReinforcementArea - 415.067717762872))->toBeLessThan(0.000001)
        ->and($result::DESIGN_MOMENT_IN_NEWTON_MILLIMETRES_UNIT)->toBe('N·mm')
        ->and($result::AREA_UNIT)->toBe('mm²')
        ->and($result::FORMULA)->toBe('As_req = MEd_Nmm / (fyd × z)');
});

it('keeps a zero design moment valid without applying a minimum reinforcement', function () {
    $result = beamRequiredTensionReinforcementCalculator()->calculate(
        requiredTensionReinforcementMoment(0),
        requiredTensionReinforcementSteel(),
        requiredTensionReinforcementLeverArm(),
    );

    expect($result->designMomentInNewtonMillimetres)->toBe(0.0)
        ->and($result->requiredReinforcementArea)->toBe(0.0);
});

it('rejects invalid required-tension-reinforcement inputs', function (
    BeamBendingMoment $moment,
    BeamFlexuralSteelDesignStrength $steel,
    BeamLeverArmResult $leverArm,
    BeamRequiredTensionReinforcementRejectionReason $reason,
) {
    try {
        beamRequiredTensionReinforcementCalculator()->calculate($moment, $steel, $leverArm);
    } catch (BeamRequiredTensionReinforcementException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid required-tension-reinforcement input to be rejected.');
})->with([
    'non-positive fyd' => [requiredTensionReinforcementMoment(), requiredTensionReinforcementSteel(0), requiredTensionReinforcementLeverArm(), BeamRequiredTensionReinforcementRejectionReason::INVALID_STEEL_DESIGN_STRENGTH],
    'non-positive z' => [requiredTensionReinforcementMoment(), requiredTensionReinforcementSteel(), requiredTensionReinforcementLeverArm(0), BeamRequiredTensionReinforcementRejectionReason::INVALID_LEVER_ARM],
]);

it('chains the beam input through MEd fyd z into the required reinforcement area', function () {
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
    $leverArm = app(BeamLeverArmCalculator::class)->calculate($depth, $neutralAxis);
    $result = beamRequiredTensionReinforcementCalculator()->calculate($bending->ultimate, $strengths->steel, $leverArm);
    $expectedArea = $bending->ultimate->maximumMoment * 1_000_000 / ($strengths->steel->fyd * $leverArm->leverArm);

    expect(abs($bending->ultimate->maximumMoment - 95.45859375))->toBeLessThan(0.000000001)
        ->and(abs($strengths->steel->fyd - 500 / 1.15))->toBeLessThan(0.000000001)
        ->and(abs($leverArm->leverArm - 528.96131457381))->toBeLessThan(0.000000001)
        ->and(abs($result->requiredReinforcementArea - $expectedArea))->toBeLessThan(0.000000001)
        ->and(abs($result->requiredReinforcementArea - 415.067717762872))->toBeLessThan(0.000001);
});
