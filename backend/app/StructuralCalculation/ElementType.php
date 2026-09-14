<?php

namespace App\StructuralCalculation;

/** Type d'élément structurel sélectionné par un module de calcul. */
enum ElementType: string
{
    case BEAM = 'BEAM';
    case SLAB = 'SLAB';
}
