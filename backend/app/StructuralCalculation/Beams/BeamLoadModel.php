<?php

namespace App\StructuralCalculation\Beams;

/** Modèles conceptuellement reconnus ; seul UNIFORMLY_DISTRIBUTED appartient au V1. */
enum BeamLoadModel: string
{
    case UNIFORMLY_DISTRIBUTED = 'UNIFORMLY_DISTRIBUTED';
    case POINT_LOAD = 'POINT_LOAD';
    case TRIANGULAR = 'TRIANGULAR';
    case APPLIED_MOMENT = 'APPLIED_MOMENT';
}
