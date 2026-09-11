<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamServiceabilityCombination;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationsResult;
use App\StructuralCalculation\Beams\BeamShearForceException;
use App\StructuralCalculation\Beams\BeamShearForceRejectionReason;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationResult;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamBendingMomentCalculator;
use App\StructuralCalculation\Beams\SimplySupportedBeamShearForceCalculator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

function simplySupportedShearForceCalculator(): SimplySupportedBeamShearForceCalculator
{
    return app(SimplySupportedBeamShearForceCalculator::class);
}

function shearUltimateCombination(float $lineLoad): BeamUltimateCombinationResult
{
    return new BeamUltimateCombinationResult(0, 1, 0, 0, 1, 0, $lineLoad, FundamentalUltimateCombinationExpression::EN1990_6_10);
}

function shearServiceabilityCombinations(float $characteristic, float $frequent, float $quasiPermanent): BeamServiceabilityCombinationsResult
{
    return new BeamServiceabilityCombinationsResult(
        new BeamServiceabilityCombination(0, 0, 1, 0, $characteristic, $characteristic, ServiceabilityCombinationExpression::EN1990_6_14, 'wSlsCharacteristic = Gk_total + Qk'),
        new BeamServiceabilityCombination(0, 0, 0.5, 0, $frequent, $frequent, ServiceabilityCombinationExpression::EN1990_6_15, 'wSlsFrequent = Gk_total + ψ1 × Qk'),
        new BeamServiceabilityCombination(0, 0, 0.3, 0, $quasiPermanent, $quasiPermanent, ServiceabilityCombinationExpression::EN1990_6_16, 'wSlsQuasiPermanent = Gk_total + ψ2 × Qk'),
    );
}

function shearConfiguration(BeamSupportSystem $supportSystem = BeamSupportSystem::SIMPLY_SUPPORTED, BeamLoadModel $loadModel = BeamLoadModel::UNIFORMLY_DISTRIBUTED): BeamCalculationConfiguration
{
    $configuration = BeamCalculationConfiguration::mvp();

    return new BeamCalculationConfiguration(
        $configuration->calculationMode,
        $configuration->elementType,
        $configuration->materialType,
        $configuration->sectionType,
        $supportSystem,
        $loadModel,
        $configuration->designCodeProfile,
        $configuration->designSituation,
    );
}

it('calculates the reference ultimate and serviceability shear forces', function () {
    $result = simplySupportedShearForceCalculator()->calculate(
        shearConfiguration(),
        new BeamGeometry(6500, 300, 600),
        shearUltimateCombination(18.075),
        shearServiceabilityCombinations(13.0, 11.25, 10.55),
    );

    expect($result->effectiveSpan)->toBe(6.5)
        ->and($result::EFFECTIVE_SPAN_UNIT)->toBe('m')
        ->and($result->supportSystem)->toBe(BeamSupportSystem::SIMPLY_SUPPORTED)
        ->and($result->loadModel)->toBe(BeamLoadModel::UNIFORMLY_DISTRIBUTED)
        ->and($result->shearCoefficient)->toBe(0.5)
        ->and($result->ultimate->lineLoad)->toBe(18.075)
        ->and($result->ultimate->maximumAbsoluteShear)->toBe(58.74375)
        ->and($result->ultimate->leftSupportShear)->toBe(58.74375)
        ->and($result->ultimate->rightSupportShear)->toBe(-58.74375)
        ->and($result->ultimate->combinationReference)->toBe(FundamentalUltimateCombinationExpression::EN1990_6_10)
        ->and($result->ultimate::UNIT)->toBe('kN')
        ->and($result->ultimate->formula)->toBe('Vmax = w × l_eff / 2')
        ->and($result->characteristic->maximumAbsoluteShear)->toBe(42.25)
        ->and($result->frequent->maximumAbsoluteShear)->toBe(36.5625)
        ->and($result->quasiPermanent->maximumAbsoluteShear)->toBe(34.2875)
        ->and($result->characteristic->combinationReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_14)
        ->and($result->frequent->combinationReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_15)
        ->and($result->quasiPermanent->combinationReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_16);
});

it('keeps zero line loads as zero shear forces', function () {
    $result = simplySupportedShearForceCalculator()->calculate(
        shearConfiguration(),
        new BeamGeometry(6500, 300, 600),
        shearUltimateCombination(0),
        shearServiceabilityCombinations(0, 0, 0),
    );

    expect($result->ultimate->maximumAbsoluteShear)->toBe(0.0)
        ->and($result->characteristic->maximumAbsoluteShear)->toBe(0.0)
        ->and($result->frequent->maximumAbsoluteShear)->toBe(0.0)
        ->and($result->quasiPermanent->maximumAbsoluteShear)->toBe(0.0);
});

it('rejects an invalid effective span', function () {
    try {
        simplySupportedShearForceCalculator()->calculate(
            shearConfiguration(),
            new BeamGeometry(0, 300, 600),
            shearUltimateCombination(0),
            shearServiceabilityCombinations(0, 0, 0),
        );
    } catch (BeamShearForceException $exception) {
        expect($exception->reason)->toBe(BeamShearForceRejectionReason::INVALID_EFFECTIVE_SPAN);

        return;
    }

    throw new RuntimeException('Expected the invalid effective span to be rejected.');
});

it('rejects a negative line load from any input combination', function (
    BeamUltimateCombinationResult $ultimate,
    BeamServiceabilityCombinationsResult $serviceability,
    BeamShearForceRejectionReason $reason,
) {
    try {
        simplySupportedShearForceCalculator()->calculate(
            shearConfiguration(),
            new BeamGeometry(6500, 300, 600),
            $ultimate,
            $serviceability,
        );
    } catch (BeamShearForceException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected the negative line load to be rejected.');
})->with([
    'ultimate' => [shearUltimateCombination(-0.1), shearServiceabilityCombinations(0, 0, 0), BeamShearForceRejectionReason::INVALID_ULTIMATE_LINE_LOAD],
    'characteristic serviceability' => [shearUltimateCombination(0), shearServiceabilityCombinations(-0.1, 0, 0), BeamShearForceRejectionReason::INVALID_CHARACTERISTIC_SERVICEABILITY_LINE_LOAD],
    'frequent serviceability' => [shearUltimateCombination(0), shearServiceabilityCombinations(0, -0.1, 0), BeamShearForceRejectionReason::INVALID_FREQUENT_SERVICEABILITY_LINE_LOAD],
    'quasi-permanent serviceability' => [shearUltimateCombination(0), shearServiceabilityCombinations(0, 0, -0.1), BeamShearForceRejectionReason::INVALID_QUASI_PERMANENT_SERVICEABILITY_LINE_LOAD],
]);

it('rejects a structural configuration outside the static MVP model', function (BeamCalculationConfiguration $configuration, BeamShearForceRejectionReason $reason) {
    try {
        simplySupportedShearForceCalculator()->calculate(
            $configuration,
            new BeamGeometry(6500, 300, 600),
            shearUltimateCombination(0),
            shearServiceabilityCombinations(0, 0, 0),
        );
    } catch (BeamShearForceException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected the unsupported structural configuration to be rejected.');
})->with([
    'continuous beam' => [shearConfiguration(BeamSupportSystem::CONTINUOUS), BeamShearForceRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM],
    'point load' => [shearConfiguration(BeamSupportSystem::SIMPLY_SUPPORTED, BeamLoadModel::POINT_LOAD), BeamShearForceRejectionReason::UNSUPPORTED_LOAD_MODEL],
]);

it('chains the complete reference input through actions, combinations, bending and shear analysis', function () {
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
    $shear = simplySupportedShearForceCalculator()->calculate($setup->configuration, $setup->geometry, $ultimate, $serviceability);

    expect($selfWeight->characteristicLineLoad)->toBe(4.5)
        ->and($actions->permanent->totalPermanentLoad)->toBe(9.5)
        ->and(abs($ultimate->designLineLoad - 18.075))->toBeLessThan(0.000000001)
        ->and(abs($bending->ultimate->maximumMoment - 95.45859375))->toBeLessThan(0.000000001)
        ->and(abs($shear->ultimate->maximumAbsoluteShear - 58.74375))->toBeLessThan(0.000000001)
        ->and($shear->characteristic->maximumAbsoluteShear)->toBe(42.25)
        ->and($shear->frequent->maximumAbsoluteShear)->toBe(36.5625)
        ->and($shear->quasiPermanent->maximumAbsoluteShear)->toBe(34.2875);
});
