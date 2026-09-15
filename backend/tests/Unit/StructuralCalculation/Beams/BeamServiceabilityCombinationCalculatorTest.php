<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCharacteristicActionsResult;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationException;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationRejectionReason;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\CharacteristicPermanentActions;
use App\StructuralCalculation\Beams\CharacteristicVariableAction;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SelfWeightResult;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Profiles\CombinationFactors;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

function beamServiceabilityCalculator(): BeamServiceabilityCombinationCalculator
{
    return app(BeamServiceabilityCombinationCalculator::class);
}

function serviceabilityActions(
    float $gkTotal,
    float $qk,
    VariableActionCategory $category = VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS,
): BeamCharacteristicActionsResult {
    $selfWeightLoad = min(4.5, $gkTotal);

    return new BeamCharacteristicActionsResult(
        new CharacteristicPermanentActions(
            new SelfWeightResult($selfWeightLoad > 0, 300, 600, 0.18, new ReinforcedConcreteUnitWeight(25), $selfWeightLoad),
            $gkTotal - $selfWeightLoad,
            $gkTotal,
        ),
        new CharacteristicVariableAction($category, $qk),
    );
}

function frenchServiceabilityProfile()
{
    return app(FrenchEurocodeProfileRepository::class)->get();
}

it('calculates the three EN 1990 serviceability combinations from profile factors', function () {
    $profile = frenchServiceabilityProfile();
    $factors = $profile->combinationFactorsFor(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS);
    $result = beamServiceabilityCalculator()->calculate(serviceabilityActions(9.5, 3.5), $profile);

    expect($factors)->not->toBeNull()
        ->and($result->characteristic->permanentCharacteristicLoad)->toBe(9.5)
        ->and($result->characteristic->variableCharacteristicLoad)->toBe(3.5)
        ->and($result->characteristic->variableFactor)->toBe(1.0)
        ->and($result->characteristic->permanentContribution)->toBe(9.5)
        ->and($result->characteristic->variableContribution)->toBe(3.5)
        ->and($result->characteristic->resultingLineLoad)->toBe(13.0)
        ->and($result->characteristic->expressionReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_14)
        ->and($result->frequent->variableFactor)->toBe($factors->psi1)
        ->and($result->frequent->variableContribution)->toBe(1.75)
        ->and($result->frequent->resultingLineLoad)->toBe(11.25)
        ->and($result->frequent->expressionReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_15)
        ->and($result->quasiPermanent->variableFactor)->toBe($factors->psi2)
        ->and(abs($result->quasiPermanent->variableContribution - 1.05))->toBeLessThan(0.000000001)
        ->and(abs($result->quasiPermanent->resultingLineLoad - 10.55))->toBeLessThan(0.000000001)
        ->and($result->quasiPermanent->expressionReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_16)
        ->and($result->characteristic::UNIT)->toBe('kN/m');
});

it('does not apply psi0 to the only leading variable action in the characteristic combination', function () {
    $profile = frenchServiceabilityProfile();
    $factors = $profile->combinationFactorsFor(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS);
    $gkTotal = 9.5;
    $qk = 3.5;
    $result = beamServiceabilityCalculator()->calculate(serviceabilityActions($gkTotal, $qk), $profile);

    expect($factors)->not->toBeNull()
        ->and($result->characteristic->resultingLineLoad)->toBe($gkTotal + $qk)
        ->and($result->characteristic->resultingLineLoad)->not->toBe($gkTotal + $factors->psi0 * $qk)
        ->and($result->characteristic->formula)->toBe('wSlsCharacteristic = Gk_total + Qk')
        ->and(property_exists($result->characteristic, 'gammaG'))->toBeFalse()
        ->and(property_exists($result->characteristic, 'gammaQ'))->toBeFalse();
});

it('uses psi1 and psi2 supplied by the selected profile rather than calculator constants', function () {
    $referenceProfile = frenchServiceabilityProfile();
    $profile = new DesignCodeProfile(
        identifier: $referenceProfile->identifier,
        materialSafetyFactors: $referenceProfile->materialSafetyFactors,
        actionSafetyFactors: $referenceProfile->actionSafetyFactors,
        fundamentalUltimateCombinationExpression: $referenceProfile->fundamentalUltimateCombinationExpression,
        coverRequirements: $referenceProfile->coverRequirements,
        beamLongitudinalReinforcementRequirements: $referenceProfile->beamLongitudinalReinforcementRequirements,
        beamConcreteShearResistanceRequirements: $referenceProfile->beamConcreteShearResistanceRequirements,
        beamCrackWidthRequirements: $referenceProfile->beamCrackWidthRequirements,
        beamDeflectionRequirements: $referenceProfile->beamDeflectionRequirements,
        beamServiceStressRequirements: $referenceProfile->beamServiceStressRequirements,
        reinforcementSpacingRequirements: $referenceProfile->reinforcementSpacingRequirements,
        slabReinforcementRequirements: $referenceProfile->slabReinforcementRequirements,
        combinationFactorsByActionCategory: [
            'A' => new CombinationFactors(psi0: 0.6, psi1: 0.4, psi2: 0.2),
        ],
    );

    $result = beamServiceabilityCalculator()->calculate(serviceabilityActions(9.5, 3.5), $profile);

    expect($result->frequent->variableFactor)->toBe(0.4)
        ->and($result->frequent->resultingLineLoad)->toBe(10.9)
        ->and($result->quasiPermanent->variableFactor)->toBe(0.2)
        ->and($result->quasiPermanent->resultingLineLoad)->toBe(10.2);
});

it('keeps zero characteristic action cases mathematically explicit', function (float $gkTotal, float $qk, array $expected) {
    $result = beamServiceabilityCalculator()->calculate(serviceabilityActions($gkTotal, $qk), frenchServiceabilityProfile());

    expect(abs($result->characteristic->resultingLineLoad - $expected['characteristic']))->toBeLessThan(0.000000001)
        ->and(abs($result->frequent->resultingLineLoad - $expected['frequent']))->toBeLessThan(0.000000001)
        ->and(abs($result->quasiPermanent->resultingLineLoad - $expected['quasiPermanent']))->toBeLessThan(0.000000001);
})->with([
    'zero Qk' => [9.5, 0.0, ['characteristic' => 9.5, 'frequent' => 9.5, 'quasiPermanent' => 9.5]],
    'zero Gk total' => [0.0, 3.5, ['characteristic' => 3.5, 'frequent' => 1.75, 'quasiPermanent' => 1.05]],
    'zero actions' => [0.0, 0.0, ['characteristic' => 0.0, 'frequent' => 0.0, 'quasiPermanent' => 0.0]],
]);

it('rejects actions with no combination factors in the selected profile', function () {
    try {
        beamServiceabilityCalculator()->calculate(
            serviceabilityActions(9.5, 3.5, VariableActionCategory::B_OFFICE_AREAS),
            frenchServiceabilityProfile(),
        );
    } catch (BeamServiceabilityCombinationException $exception) {
        expect($exception->reason)->toBe(BeamServiceabilityCombinationRejectionReason::MISSING_VARIABLE_ACTION_FACTORS);

        return;
    }

    throw new RuntimeException('Expected unsupported variable-action category to be rejected.');
});

it('chains validated input through self weight and characteristic actions into the ELS combinations', function () {
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
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $selfWeight = app(SelfWeightCalculator::class)->calculate(
        $setup->geometry,
        $setup->permanentLoads->includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
    $actions = app(CharacteristicActionsCalculator::class)->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);
    $result = beamServiceabilityCalculator()->calculate($actions, frenchServiceabilityProfile());

    expect($selfWeight->characteristicLineLoad)->toBe(4.5)
        ->and($actions->permanent->totalPermanentLoad)->toBe(9.5)
        ->and($result->characteristic->resultingLineLoad)->toBe(13.0)
        ->and($result->frequent->resultingLineLoad)->toBe(11.25)
        ->and(abs($result->quasiPermanent->resultingLineLoad - 10.55))->toBeLessThan(0.000000001);
});
