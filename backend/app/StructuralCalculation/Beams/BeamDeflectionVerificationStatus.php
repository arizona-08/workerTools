<?php

namespace App\StructuralCalculation\Beams;

enum BeamDeflectionVerificationStatus: string
{
    case COMPLIANT = 'COMPLIANT';
    case NOT_COMPLIANT = 'NOT_COMPLIANT';
    case CALCULATION_METHOD_NOT_SUPPORTED = 'CALCULATION_METHOD_NOT_SUPPORTED';
    case NOT_CHECKED = 'NOT_CHECKED';
}
