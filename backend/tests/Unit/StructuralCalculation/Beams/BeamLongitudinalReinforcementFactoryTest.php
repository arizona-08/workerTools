<?php

use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementException;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementFactory;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementRejectionReason;

function beamLongitudinalReinforcementFactory(): BeamLongitudinalReinforcementFactory
{
    return app(BeamLongitudinalReinforcementFactory::class);
}

it('derives provided steel area from 4 HA16 without accepting a client area', function () {
    $reinforcement = beamLongitudinalReinforcementFactory()->fromValues(BeamCalculationMode::VERIFICATION, 4, 16);

    expect($reinforcement->tensionBarCount)->toBe(4)
        ->and($reinforcement->tensionBarDiameter)->toBe(16.0)
        ->and($reinforcement->tensionRebarLayers)->toBe(1)
        ->and(abs($reinforcement->providedSteelArea - 804.247719))->toBeLessThan(0.000001)
        ->and(array_keys(get_object_vars($reinforcement)))->toEqual(['providedSteelArea', 'tensionBarCount', 'tensionBarDiameter', 'tensionRebarLayers']);
});

it('derives provided steel area from 2 HA20', function () {
    $reinforcement = beamLongitudinalReinforcementFactory()->fromValues(BeamCalculationMode::VERIFICATION, 2, 20);

    expect(abs($reinforcement->providedSteelArea - 628.318531))->toBeLessThan(0.000001);
});

it('does not require reinforcement in design mode and rejects incoherent design reinforcement', function () {
    expect(beamLongitudinalReinforcementFactory()->fromValues(BeamCalculationMode::DESIGN))->toBeNull();

    beamLongitudinalReinforcementFactory()->fromValues(BeamCalculationMode::DESIGN, 4, 16);
})->throws(BeamLongitudinalReinforcementException::class, BeamLongitudinalReinforcementRejectionReason::REINFORCEMENT_NOT_ALLOWED_IN_DESIGN->value);

it('rejects missing or unsupported verification reinforcement', function (mixed $count, mixed $diameter, mixed $layers, BeamLongitudinalReinforcementRejectionReason $reason) {
    try {
        beamLongitudinalReinforcementFactory()->fromValues(BeamCalculationMode::VERIFICATION, $count, $diameter, $layers);
    } catch (BeamLongitudinalReinforcementException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid longitudinal reinforcement to be rejected.');
})->with([
    'missing bar count' => [null, 16, 1, BeamLongitudinalReinforcementRejectionReason::MISSING_TENSION_BAR_COUNT],
    'zero bar count' => [0, 16, 1, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_COUNT],
    'negative bar count' => [-1, 16, 1, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_COUNT],
    'decimal bar count' => [2.5, 16, 1, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_COUNT],
    'missing diameter' => [4, null, 1, BeamLongitudinalReinforcementRejectionReason::MISSING_TENSION_BAR_DIAMETER],
    'unsupported diameter' => [4, 18, 1, BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_DIAMETER],
    'multiple layers' => [4, 16, 2, BeamLongitudinalReinforcementRejectionReason::UNSUPPORTED_TENSION_REBAR_LAYERS],
]);
