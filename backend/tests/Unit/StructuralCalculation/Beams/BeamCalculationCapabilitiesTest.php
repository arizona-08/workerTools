<?php

use App\StructuralCalculation\Beams\BeamCalculationCapabilities;

it('exposes only materials that the complete beam V1 can process', function () {
    $capabilities = app(BeamCalculationCapabilities::class);

    expect($capabilities->supportedConcreteClasses())->toBe(['C20/25', 'C25/30', 'C30/37'])
        ->and($capabilities->supportedSteelGrades())->toBe(['B500B'])
        ->and($capabilities->supportedExposureClasses())->toBe([
            ['code' => 'XC1', 'label' => 'Sec ou humide en permanence'],
        ])
        ->and($capabilities->supportsExposureClass('XC4'))->toBeFalse();
});
