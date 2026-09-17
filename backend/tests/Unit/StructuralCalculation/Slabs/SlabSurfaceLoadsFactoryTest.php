<?php

use App\StructuralCalculation\Slabs\SlabSurfaceLoadsException;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadsFactory;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadsRejectionReason;

it('accepts independent non-negative characteristic surface loads', function () {
    $loads = app(SlabSurfaceLoadsFactory::class)->fromValues(1.5, 1.0, 0.5, 2.0);

    expect($loads->finishes)->toBe(1.5)
        ->and($loads->partitions)->toBe(1.0)
        ->and($loads->otherPermanent)->toBe(0.5)
        ->and($loads->imposedLoad)->toBe(2.0)
        ->and($loads::UNIT)->toBe('kN/m²');
});

it('rejects missing, negative and non-finite surface loads', function (mixed $finishes, mixed $partitions, mixed $otherPermanent, mixed $imposedLoad, SlabSurfaceLoadsRejectionReason $reason) {
    try {
        app(SlabSurfaceLoadsFactory::class)->fromValues($finishes, $partitions, $otherPermanent, $imposedLoad);
    } catch (SlabSurfaceLoadsException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid slab surface load to be rejected.');
})->with([
    'missing finishes' => [null, 0, 0, 0, SlabSurfaceLoadsRejectionReason::MISSING_FINISHES],
    'negative finishes' => [-1, 0, 0, 0, SlabSurfaceLoadsRejectionReason::INVALID_FINISHES],
    'negative partitions' => [0, -1, 0, 0, SlabSurfaceLoadsRejectionReason::INVALID_PARTITIONS],
    'negative other permanent' => [0, 0, -1, 0, SlabSurfaceLoadsRejectionReason::INVALID_OTHER_PERMANENT],
    'negative imposed load' => [0, 0, 0, -1, SlabSurfaceLoadsRejectionReason::INVALID_IMPOSED_LOAD],
    'non-finite imposed load' => [0, 0, 0, INF, SlabSurfaceLoadsRejectionReason::INVALID_IMPOSED_LOAD],
]);
