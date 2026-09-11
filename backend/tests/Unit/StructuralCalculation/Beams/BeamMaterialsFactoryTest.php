<?php

use App\StructuralCalculation\Beams\BeamMaterialsException;
use App\StructuralCalculation\Beams\BeamMaterialsFactory;
use App\StructuralCalculation\Beams\BeamMaterialsRejectionReason;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

function beamMaterialsFactory(): BeamMaterialsFactory
{
    return app(BeamMaterialsFactory::class);
}

it('resolves valid material identifiers through the existing repositories', function () {
    $materials = beamMaterialsFactory()->fromValues('C30/37', 'B500B', ['XC1', 'XC4']);

    expect($materials->concreteClass)->toBe(ConcreteStrengthClass::C30_37)
        ->and($materials->steelGrade)->toBe(ReinforcementSteelGrade::B500B)
        ->and($materials->exposureClasses)->toBe([ExposureClassCode::XC1, ExposureClassCode::XC4])
        ->and(property_exists($materials, 'fck'))->toBeFalse()
        ->and(property_exists($materials, 'fyd'))->toBeFalse();
});

it('rejects missing, unknown and duplicate material identifiers', function (mixed $concreteClass, mixed $steelGrade, mixed $exposureClasses, BeamMaterialsRejectionReason $reason) {
    try {
        beamMaterialsFactory()->fromValues($concreteClass, $steelGrade, $exposureClasses);
    } catch (BeamMaterialsException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected materials to be rejected.');
})->with([
    'missing concrete' => [null, 'B500B', ['XC1'], BeamMaterialsRejectionReason::MISSING_CONCRETE_CLASS],
    'unknown concrete' => ['SUPER_CONCRETE', 'B500B', ['XC1'], BeamMaterialsRejectionReason::INVALID_CONCRETE_CLASS],
    'missing steel' => ['C30/37', null, ['XC1'], BeamMaterialsRejectionReason::MISSING_STEEL_GRADE],
    'unknown steel' => ['C30/37', 'B900Z', ['XC1'], BeamMaterialsRejectionReason::INVALID_STEEL_GRADE],
    'missing exposures' => ['C30/37', 'B500B', [], BeamMaterialsRejectionReason::MISSING_EXPOSURE_CLASSES],
    'unknown exposure' => ['C30/37', 'B500B', ['XZ1'], BeamMaterialsRejectionReason::INVALID_EXPOSURE_CLASS],
    'duplicate exposure' => ['C30/37', 'B500B', ['XC1', 'XC1'], BeamMaterialsRejectionReason::DUPLICATE_EXPOSURE_CLASS],
]);
