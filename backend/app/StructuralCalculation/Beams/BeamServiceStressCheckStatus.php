<?php

namespace App\StructuralCalculation\Beams;

enum BeamServiceStressCheckStatus: string
{
    case COMPLIANT = 'COMPLIANT';
    case NOT_COMPLIANT = 'NOT_COMPLIANT';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';
    case NOT_CHECKED = 'NOT_CHECKED';
}
