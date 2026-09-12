<?php

namespace App\StructuralCalculation\Beams;

/** Statut local de VRd,max, sans conclusion de conformité globale. */
enum BeamMaximumShearResistanceStatus: string
{
    case MAXIMUM_SHEAR_RESISTANCE_OK = 'MAXIMUM_SHEAR_RESISTANCE_OK';
    case MAXIMUM_SHEAR_RESISTANCE_EXCEEDED = 'MAXIMUM_SHEAR_RESISTANCE_EXCEEDED';
}
