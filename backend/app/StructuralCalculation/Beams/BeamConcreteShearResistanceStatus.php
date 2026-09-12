<?php

namespace App\StructuralCalculation\Beams;

/** Statut limité au premier contrôle VRd,c, sans conclusion globale de cisaillement. */
enum BeamConcreteShearResistanceStatus: string
{
    case SHEAR_REINFORCEMENT_NOT_REQUIRED_BY_VRDC_CHECK = 'SHEAR_REINFORCEMENT_NOT_REQUIRED_BY_VRDC_CHECK';
    case SHEAR_REINFORCEMENT_REQUIRED = 'SHEAR_REINFORCEMENT_REQUIRED';
}
