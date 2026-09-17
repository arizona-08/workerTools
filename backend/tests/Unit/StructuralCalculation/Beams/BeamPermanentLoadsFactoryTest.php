<?php

use App\StructuralCalculation\Beams\BeamPermanentLoadsException;
use App\StructuralCalculation\Beams\BeamPermanentLoadsFactory;
use App\StructuralCalculation\Beams\BeamPermanentLoadsRejectionReason;

function beamPermanentLoadsFactory(): BeamPermanentLoadsFactory
{
    return app(BeamPermanentLoadsFactory::class);
}

it('creates permanent loads with self weight enabled and no additional load', function () {
    $loads = beamPermanentLoadsFactory()->fromValues(true, 0);

    expect($loads->includeSelfWeight)->toBeTrue()
        ->and($loads->additionalPermanentLoad)->toBe(0.0)
        ->and(array_keys(get_object_vars($loads)))->toEqual(['includeSelfWeight', 'additionalPermanentLoad']);
});

it('keeps an additional permanent load when self weight is disabled', function () {
    $loads = beamPermanentLoadsFactory()->fromValues(false, 5.25);

    expect($loads->includeSelfWeight)->toBeFalse()
        ->and($loads->additionalPermanentLoad)->toBe(5.25);
});

it('rejects missing permanent-load values', function (mixed $includeSelfWeight, mixed $additionalPermanentLoad, BeamPermanentLoadsRejectionReason $reason) {
    try {
        beamPermanentLoadsFactory()->fromValues($includeSelfWeight, $additionalPermanentLoad);
    } catch (BeamPermanentLoadsException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected missing permanent-load value to be rejected.');
})->with([
    'self-weight flag' => [null, 0, BeamPermanentLoadsRejectionReason::MISSING_INCLUDE_SELF_WEIGHT],
    'additional load' => [true, null, BeamPermanentLoadsRejectionReason::MISSING_ADDITIONAL_PERMANENT_LOAD],
]);

it('rejects invalid permanent-load values', function (mixed $includeSelfWeight, mixed $additionalPermanentLoad, BeamPermanentLoadsRejectionReason $reason) {
    try {
        beamPermanentLoadsFactory()->fromValues($includeSelfWeight, $additionalPermanentLoad);
    } catch (BeamPermanentLoadsException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid permanent-load value to be rejected.');
})->with([
    'non-boolean self-weight flag' => ['true', 0, BeamPermanentLoadsRejectionReason::INVALID_INCLUDE_SELF_WEIGHT],
    'negative additional load' => [true, -0.01, BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD],
    'non-numeric additional load' => [false, 'five', BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD],
    'infinite additional load' => [true, INF, BeamPermanentLoadsRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD],
]);
