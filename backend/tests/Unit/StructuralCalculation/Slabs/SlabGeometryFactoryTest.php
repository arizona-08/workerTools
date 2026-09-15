<?php

use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabGeometryException;
use App\StructuralCalculation\Slabs\SlabGeometryFactory;
use App\StructuralCalculation\Slabs\SlabGeometryRejectionReason;

function slabGeometryFactory(): SlabGeometryFactory
{
    return app(SlabGeometryFactory::class);
}

it('creates geometry in millimetres and supplies the fixed unit calculation strip', function () {
    $geometry = slabGeometryFactory()->fromInternalValues(5350, 200);

    expect($geometry->effectiveSpan)->toBe(5350.0)
        ->and($geometry->thickness)->toBe(200.0)
        ->and($geometry->calculationStripWidth)->toBe(1000.0)
        ->and(SlabGeometry::CALCULATION_STRIP_WIDTH_MM)->toBe(1000.0);
});

it('does not accept a client-provided calculation strip width', function () {
    expect((new ReflectionMethod(SlabGeometryFactory::class, 'fromInternalValues'))->getNumberOfParameters())->toBe(2);
});

it('rejects missing, non-positive and non-finite slab lengths', function (mixed $effectiveSpan, mixed $thickness, SlabGeometryRejectionReason $reason) {
    try {
        slabGeometryFactory()->fromInternalValues($effectiveSpan, $thickness);
    } catch (SlabGeometryException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid slab geometry to be rejected.');
})->with([
    'missing span' => [null, 200, SlabGeometryRejectionReason::MISSING_EFFECTIVE_SPAN],
    'zero span' => [0, 200, SlabGeometryRejectionReason::INVALID_EFFECTIVE_SPAN],
    'negative span' => [-5350, 200, SlabGeometryRejectionReason::INVALID_EFFECTIVE_SPAN],
    'non-finite span' => [INF, 200, SlabGeometryRejectionReason::INVALID_EFFECTIVE_SPAN],
    'missing thickness' => [5350, null, SlabGeometryRejectionReason::MISSING_THICKNESS],
    'zero thickness' => [5350, 0, SlabGeometryRejectionReason::INVALID_THICKNESS],
    'negative thickness' => [5350, -200, SlabGeometryRejectionReason::INVALID_THICKNESS],
    'non-numeric thickness' => [5350, 'two hundred', SlabGeometryRejectionReason::INVALID_THICKNESS],
]);
