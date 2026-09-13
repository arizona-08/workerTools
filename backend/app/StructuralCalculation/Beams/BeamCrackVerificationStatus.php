<?php

namespace App\StructuralCalculation\Beams;

enum BeamCrackVerificationStatus: string
{
    case COMPLIANT = 'COMPLIANT';
    case NOT_COMPLIANT = 'NOT_COMPLIANT';
    case NOT_CHECKED = 'NOT_CHECKED';
}
