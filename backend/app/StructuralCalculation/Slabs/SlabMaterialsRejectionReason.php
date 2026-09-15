<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamMaterialsRejectionReason;

enum SlabMaterialsRejectionReason: string
{
    case MISSING_CONCRETE_CLASS = 'MISSING_CONCRETE_CLASS';
    case INVALID_CONCRETE_CLASS = 'INVALID_CONCRETE_CLASS';
    case UNSUPPORTED_CONCRETE_CLASS = 'UNSUPPORTED_CONCRETE_CLASS';
    case MISSING_STEEL_GRADE = 'MISSING_STEEL_GRADE';
    case INVALID_STEEL_GRADE = 'INVALID_STEEL_GRADE';
    case UNSUPPORTED_STEEL_GRADE = 'UNSUPPORTED_STEEL_GRADE';
    case MISSING_EXPOSURE_CLASS = 'MISSING_EXPOSURE_CLASS';
    case INVALID_EXPOSURE_CLASS = 'INVALID_EXPOSURE_CLASS';
    case UNSUPPORTED_EXPOSURE_CLASS = 'UNSUPPORTED_EXPOSURE_CLASS';

    public static function fromBeamReason(BeamMaterialsRejectionReason $reason): self
    {
        return match ($reason) {
            BeamMaterialsRejectionReason::MISSING_CONCRETE_CLASS => self::MISSING_CONCRETE_CLASS,
            BeamMaterialsRejectionReason::INVALID_CONCRETE_CLASS => self::INVALID_CONCRETE_CLASS,
            BeamMaterialsRejectionReason::UNSUPPORTED_CONCRETE_CLASS => self::UNSUPPORTED_CONCRETE_CLASS,
            BeamMaterialsRejectionReason::MISSING_STEEL_GRADE => self::MISSING_STEEL_GRADE,
            BeamMaterialsRejectionReason::INVALID_STEEL_GRADE => self::INVALID_STEEL_GRADE,
            BeamMaterialsRejectionReason::UNSUPPORTED_STEEL_GRADE => self::UNSUPPORTED_STEEL_GRADE,
            BeamMaterialsRejectionReason::MISSING_EXPOSURE_CLASSES => self::MISSING_EXPOSURE_CLASS,
            BeamMaterialsRejectionReason::INVALID_EXPOSURE_CLASS,
            BeamMaterialsRejectionReason::DUPLICATE_EXPOSURE_CLASS => self::INVALID_EXPOSURE_CLASS,
            BeamMaterialsRejectionReason::UNSUPPORTED_EXPOSURE_CLASS => self::UNSUPPORTED_EXPOSURE_CLASS,
        };
    }
}
