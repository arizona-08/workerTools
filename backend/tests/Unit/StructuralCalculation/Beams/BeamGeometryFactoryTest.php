<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationSetup;
use App\StructuralCalculation\Beams\BeamGeometryException;
use App\StructuralCalculation\Beams\BeamGeometryFactory;
use App\StructuralCalculation\Beams\BeamGeometryRejectionReason;

function beamGeometryFactory(): BeamGeometryFactory
{
    return app(BeamGeometryFactory::class);
}

it('creates a valid beam geometry from millimetre payload values', function () {
    $geometry = beamGeometryFactory()->fromInternalValues(6500, 300, 600);

    expect($geometry->effectiveSpan)->toBe(6500.0)
        ->and($geometry->width)->toBe(300.0)
        ->and($geometry->height)->toBe(600.0);
});

it('rejects missing geometry fields', function (mixed $effectiveSpan, mixed $width, mixed $height, BeamGeometryRejectionReason $reason) {
    try {
        beamGeometryFactory()->fromInternalValues($effectiveSpan, $width, $height);
    } catch (BeamGeometryException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected missing geometry to be rejected.');
})->with([
    'effective span' => [null, 300, 600, BeamGeometryRejectionReason::MISSING_EFFECTIVE_SPAN],
    'width' => [6500, null, 600, BeamGeometryRejectionReason::MISSING_WIDTH],
    'height' => [6500, 300, null, BeamGeometryRejectionReason::MISSING_HEIGHT],
]);

it('rejects non-positive and non-numeric geometry fields', function (mixed $effectiveSpan, mixed $width, mixed $height, BeamGeometryRejectionReason $reason) {
    try {
        beamGeometryFactory()->fromInternalValues($effectiveSpan, $width, $height);
    } catch (BeamGeometryException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid geometry to be rejected.');
})->with([
    'zero effective span' => [0, 300, 600, BeamGeometryRejectionReason::INVALID_EFFECTIVE_SPAN],
    'negative width' => [6500, -300, 600, BeamGeometryRejectionReason::INVALID_WIDTH],
    'negative height' => [6500, 300, -600, BeamGeometryRejectionReason::INVALID_HEIGHT],
    'non-numeric effective span' => ['six metres', 300, 600, BeamGeometryRejectionReason::INVALID_EFFECTIVE_SPAN],
]);

it('keeps the configuration and geometry as distinct parts of the future calculation setup', function () {
    $setup = new BeamCalculationSetup(
        BeamCalculationConfiguration::mvp(),
        beamGeometryFactory()->fromInternalValues(6500, 300, 600),
    );

    expect($setup->configuration->calculationMode->value)->toBe('DESIGN')
        ->and($setup->geometry->width)->toBe(300.0);
});
