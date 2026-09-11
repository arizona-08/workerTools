<?php

use App\StructuralCalculation\Beams\BeamCalculationInputException;
use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationInputRejectionReason;
use App\StructuralCalculation\Beams\BeamConfigurationException;
use App\StructuralCalculation\Beams\BeamConfigurationRejectionReason;
use App\StructuralCalculation\Beams\BeamGeometryException;
use App\StructuralCalculation\Beams\BeamGeometryRejectionReason;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementException;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementRejectionReason;
use App\StructuralCalculation\Beams\BeamMaterialsException;
use App\StructuralCalculation\Beams\BeamMaterialsRejectionReason;
use App\StructuralCalculation\Beams\BeamPermanentLoadsException;
use App\StructuralCalculation\Beams\BeamPermanentLoadsRejectionReason;
use App\StructuralCalculation\Beams\BeamVariableLoadException;
use App\StructuralCalculation\Beams\BeamVariableLoadRejectionReason;

function beamCalculationInputFactory(): BeamCalculationInputFactory
{
    return app(BeamCalculationInputFactory::class);
}

/** @return array<string, mixed> */
function validBeamPayload(string $mode = 'DESIGN'): array
{
    $payload = [
        'configuration' => [
            'calculationMode' => $mode,
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
    ];

    if ($mode === 'VERIFICATION') {
        $payload['reinforcement'] = ['longitudinal' => ['tension' => ['barCount' => 4, 'barDiameter' => 16, 'diameterUnit' => 'mm']]];
    }

    return $payload;
}

it('assembles complete valid design and verification payloads', function (string $mode) {
    $setup = beamCalculationInputFactory()->fromPayload(validBeamPayload($mode));

    expect($setup->configuration->calculationMode->value)->toBe($mode)
        ->and($setup->geometry->effectiveSpan)->toBe(6500.0)
        ->and($setup->materials->concreteClass->value)->toBe('C30/37')
        ->and($setup->permanentLoads->additionalPermanentLoad)->toBe(5.0)
        ->and($setup->variableLoad->characteristicLoad)->toBe(3.5);

    if ($mode === 'DESIGN') {
        expect($setup->longitudinalReinforcement)->toBeNull();
    } else {
        expect($setup->longitudinalReinforcement->providedSteelArea)->toBeGreaterThan(804.2)
            ->and($setup->longitudinalReinforcement->providedSteelArea)->toBeLessThan(804.3);
    }
})->with(['DESIGN', 'VERIFICATION']);

it('rejects invalid complete-input cases with their domain errors', function (Closure $change, string $exceptionClass, string $reason) {
    $payload = validBeamPayload('VERIFICATION');
    $change($payload);

    try {
        beamCalculationInputFactory()->fromPayload($payload);
    } catch (Throwable $exception) {
        expect($exception)->toBeInstanceOf($exceptionClass)
            ->and($exception->getMessage())->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid input to be rejected.');
})->with([
    'known but unsupported configuration' => [function (array &$payload): void {
        $payload['configuration']['sectionType'] = 'T_SECTION';
    }, BeamConfigurationException::class, BeamConfigurationRejectionReason::UNSUPPORTED_SECTION_TYPE->value],
    'unknown mode' => [function (array &$payload): void {
        $payload['configuration']['calculationMode'] = 'UNKNOWN';
    }, BeamConfigurationException::class, BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE->value],
    'invalid geometry' => [function (array &$payload): void {
        $payload['geometry']['height'] = 0;
    }, BeamGeometryException::class, BeamGeometryRejectionReason::INVALID_HEIGHT->value],
    'invalid geometry unit' => [function (array &$payload): void {
        $payload['geometry']['unit'] = 'cm';
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::INVALID_GEOMETRY_UNIT->value],
    'unknown concrete' => [function (array &$payload): void {
        $payload['materials']['concreteClass'] = 'C99/100';
    }, BeamMaterialsException::class, BeamMaterialsRejectionReason::INVALID_CONCRETE_CLASS->value],
    'unknown steel' => [function (array &$payload): void {
        $payload['materials']['steelGrade'] = 'B999';
    }, BeamMaterialsException::class, BeamMaterialsRejectionReason::INVALID_STEEL_GRADE->value],
    'unknown exposure' => [function (array &$payload): void {
        $payload['materials']['exposureClasses'] = ['UNKNOWN'];
    }, BeamMaterialsException::class, BeamMaterialsRejectionReason::INVALID_EXPOSURE_CLASS->value],
    'duplicate exposure' => [function (array &$payload): void {
        $payload['materials']['exposureClasses'] = ['XC1', 'XC1'];
    }, BeamMaterialsException::class, BeamMaterialsRejectionReason::DUPLICATE_EXPOSURE_CLASS->value],
    'negative additional permanent load' => [function (array &$payload): void {
        $payload['loads']['permanent']['additionalPermanentLoad'] = -1;
    }, BeamPermanentLoadsException::class, BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD->value],
    'invalid permanent load unit' => [function (array &$payload): void {
        $payload['loads']['permanent']['unit'] = 'kN';
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::INVALID_PERMANENT_LOAD_UNIT->value],
    'negative Qk' => [function (array &$payload): void {
        $payload['loads']['variable']['characteristicLoad'] = -1;
    }, BeamVariableLoadException::class, BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD->value],
    'category B outside MVP' => [function (array &$payload): void {
        $payload['loads']['variable']['category'] = 'B';
    }, BeamVariableLoadException::class, BeamVariableLoadRejectionReason::UNSUPPORTED_VARIABLE_ACTION_CATEGORY->value],
    'invalid variable load unit' => [function (array &$payload): void {
        $payload['loads']['variable']['unit'] = 'kN';
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::INVALID_VARIABLE_LOAD_UNIT->value],
    'missing verification reinforcement' => [function (array &$payload): void {
        unset($payload['reinforcement']);
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::MISSING_REINFORCEMENT->value],
    'unsupported bar diameter' => [function (array &$payload): void {
        $payload['reinforcement']['longitudinal']['tension']['barDiameter'] = 18;
    }, BeamLongitudinalReinforcementException::class, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_DIAMETER->value],
    'decimal bar count' => [function (array &$payload): void {
        $payload['reinforcement']['longitudinal']['tension']['barCount'] = 2.5;
    }, BeamLongitudinalReinforcementException::class, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_COUNT->value],
    'invalid diameter unit' => [function (array &$payload): void {
        $payload['reinforcement']['longitudinal']['tension']['diameterUnit'] = 'cm';
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::INVALID_REINFORCEMENT_DIAMETER_UNIT->value],
    'untrusted derived or mechanical property' => [function (array &$payload): void {
        $payload['materials']['fck'] = 99;
    }, BeamCalculationInputException::class, BeamCalculationInputRejectionReason::UNEXPECTED_PAYLOAD_PROPERTY->value],
]);

it('accepts design without reinforcement and rejects reinforcement in a strict design payload', function () {
    expect(beamCalculationInputFactory()->fromPayload(validBeamPayload('DESIGN'))->longitudinalReinforcement)->toBeNull();

    $payload = validBeamPayload('DESIGN');
    $payload['reinforcement'] = ['longitudinal' => ['tension' => ['barCount' => 4, 'barDiameter' => 16, 'diameterUnit' => 'mm']]];

    try {
        beamCalculationInputFactory()->fromPayload($payload);
    } catch (BeamLongitudinalReinforcementException $exception) {
        expect($exception->reason)->toBe(BeamLongitudinalReinforcementRejectionReason::REINFORCEMENT_NOT_ALLOWED_IN_DESIGN);

        return;
    }

    throw new RuntimeException('Expected design reinforcement to be rejected.');
});
