<?php

namespace App\StructuralCalculation\Beams;

/** Statut de disponibilité produit ; il ne pilote pas le moteur de calcul. */
enum BeamSubmoduleStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case COMING_SOON = 'COMING_SOON';
    case UNAVAILABLE = 'UNAVAILABLE';
}
