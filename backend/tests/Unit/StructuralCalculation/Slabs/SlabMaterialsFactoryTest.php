<?php

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Slabs\SlabMaterialsException;
use App\StructuralCalculation\Slabs\SlabMaterialsFactory;
use App\StructuralCalculation\Slabs\SlabMaterialsRejectionReason;

it('reuses the common concrete steel and exposure types supported by the V1', function () {
    $materials = app(SlabMaterialsFactory::class)->fromValues('C30/37', 'B500B', 'XC1');

    expect($materials->concreteClass)->toBe(ConcreteStrengthClass::C30_37)
        ->and($materials->steelGrade)->toBe(ReinforcementSteelGrade::B500B)
        ->and($materials->exposureClass)->toBe(ExposureClassCode::XC1);
});

it('rejects absent or unknown common material identifiers without a fallback', function (mixed $concrete, mixed $steel, mixed $exposure, SlabMaterialsRejectionReason $reason) {
    app(SlabMaterialsFactory::class)->fromValues($concrete, $steel, $exposure);
})->with([
    'missing concrete' => [null, 'B500B', 'XC1', SlabMaterialsRejectionReason::MISSING_CONCRETE_CLASS],
    'unknown concrete' => ['C99/99', 'B500B', 'XC1', SlabMaterialsRejectionReason::INVALID_CONCRETE_CLASS],
    'unknown steel' => ['C30/37', 'B500C', 'XC1', SlabMaterialsRejectionReason::INVALID_STEEL_GRADE],
    'missing exposure' => ['C30/37', 'B500B', null, SlabMaterialsRejectionReason::MISSING_EXPOSURE_CLASS],
    'unsupported exposure' => ['C30/37', 'B500B', 'XC4', SlabMaterialsRejectionReason::UNSUPPORTED_EXPOSURE_CLASS],
])->throws(SlabMaterialsException::class);
