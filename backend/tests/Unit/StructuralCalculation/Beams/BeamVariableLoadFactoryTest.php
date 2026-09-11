<?php

use App\StructuralCalculation\Beams\BeamVariableLoadException;
use App\StructuralCalculation\Beams\BeamVariableLoadFactory;
use App\StructuralCalculation\Beams\BeamVariableLoadRejectionReason;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

function beamVariableLoadFactory(): BeamVariableLoadFactory
{
    return app(BeamVariableLoadFactory::class);
}

it('creates the category A variable load with zero or positive Qk', function (float $qk) {
    $load = beamVariableLoadFactory()->fromValues('A', $qk);

    expect($load->category)->toBe(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS)
        ->and($load->characteristicLoad)->toBe($qk)
        ->and(array_keys(get_object_vars($load)))->toEqual(['category', 'characteristicLoad']);
})->with([0.0, 3.5]);

it('rejects missing or invalid variable-load values', function (mixed $category, mixed $qk, BeamVariableLoadRejectionReason $reason) {
    try {
        beamVariableLoadFactory()->fromValues($category, $qk);
    } catch (BeamVariableLoadException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid variable load to be rejected.');
})->with([
    'missing category' => [null, 0, BeamVariableLoadRejectionReason::MISSING_VARIABLE_ACTION_CATEGORY],
    'unknown category' => ['SNOW', 0, BeamVariableLoadRejectionReason::INVALID_VARIABLE_ACTION_CATEGORY],
    'known but unsupported category' => ['B', 0, BeamVariableLoadRejectionReason::UNSUPPORTED_VARIABLE_ACTION_CATEGORY],
    'missing Qk' => ['A', null, BeamVariableLoadRejectionReason::MISSING_CHARACTERISTIC_VARIABLE_LOAD],
    'negative Qk' => ['A', -0.01, BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD],
    'non-numeric Qk' => ['A', 'three', BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD],
    'infinite Qk' => ['A', INF, BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD],
]);

it('resolves category A factors from the profile without storing them in the variable load', function () {
    $load = beamVariableLoadFactory()->fromValues('A', 3.5);
    $factors = app(FrenchEurocodeProfileRepository::class)->get()->combinationFactorsFor($load->category);

    expect($factors)->not->toBeNull()
        ->and(property_exists($load, 'psi0'))->toBeFalse()
        ->and(property_exists($load, 'psi1'))->toBeFalse()
        ->and(property_exists($load, 'psi2'))->toBeFalse();
});
