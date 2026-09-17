<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;

/** Valeurs indépendantes : γRC=25 kN/m³, γG=1.35, γQ=1.50, ψ1=0.5, ψ2=0.3, M=wL²/8, V=wL/2. */
function qaBeamPayload(float $span, float $width, float $height, float $additionalGk, float $qk): array
{
    return [
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => $span, 'width' => $width, 'height' => $height, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => $additionalGk, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => $qk, 'unit' => 'kN/m']],
    ];
}

it('matches independently established actions, combinations and internal forces for nominal beam references', function (array $input, array $expected) {
    $result = app(BeamCalculationOrchestrator::class)->calculate(app(BeamCalculationInputFactory::class)->fromPayload($input));
    $actions = $result->details->combinations['characteristicActions'];
    $ultimate = $result->details->combinations['ultimate'];
    $serviceability = $result->details->combinations['serviceability'];
    $moments = $result->details->internalForces['bendingMoments'];
    $shears = $result->details->internalForces['shearForces'];

    expect(abs($actions->permanent->selfWeight->characteristicLineLoad - $expected['selfWeight']))->toBeLessThan(1e-10)
        ->and(abs($actions->permanent->totalPermanentLoad - $expected['gk']))->toBeLessThan(1e-10)
        ->and(abs($ultimate->designLineLoad - $expected['wed']))->toBeLessThan(1e-10)
        ->and(abs($serviceability->characteristic->resultingLineLoad - $expected['slsCharacteristic']))->toBeLessThan(1e-10)
        ->and(abs($serviceability->frequent->resultingLineLoad - $expected['slsFrequent']))->toBeLessThan(1e-10)
        ->and(abs($serviceability->quasiPermanent->resultingLineLoad - $expected['slsQuasi']))->toBeLessThan(1e-10)
        ->and(abs($moments->ultimate->maximumMoment - $expected['med']))->toBeLessThan(1e-9)
        ->and(abs($shears->ultimate->maximumAbsoluteShear - $expected['ved']))->toBeLessThan(1e-9);
})->with([
    'A — nominal 6.5 m, 300×600' => [qaBeamPayload(6500, 300, 600, 5, 3.5), ['selfWeight' => 4.5, 'gk' => 9.5, 'wed' => 18.075, 'slsCharacteristic' => 13.0, 'slsFrequent' => 11.25, 'slsQuasi' => 10.55, 'med' => 95.45859375, 'ved' => 58.74375]],
    'B — geometry 5 m, 250×500' => [qaBeamPayload(5000, 250, 500, 3, 4), ['selfWeight' => 3.125, 'gk' => 6.125, 'wed' => 14.26875, 'slsCharacteristic' => 10.125, 'slsFrequent' => 8.125, 'slsQuasi' => 7.325, 'med' => 44.58984375, 'ved' => 35.671875]],
    'C — permanent dominant 4 m, 350×550' => [qaBeamPayload(4000, 350, 550, 9, 1.5), ['selfWeight' => 4.8125, 'gk' => 13.8125, 'wed' => 20.896875, 'slsCharacteristic' => 15.3125, 'slsFrequent' => 14.5625, 'slsQuasi' => 14.2625, 'med' => 41.79375, 'ved' => 41.79375]],
]);
