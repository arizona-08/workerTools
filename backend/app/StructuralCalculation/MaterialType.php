<?php

namespace App\StructuralCalculation;

/** Famille de matériau structurel, indépendante de la géométrie de l'élément. */
enum MaterialType: string
{
    case REINFORCED_CONCRETE = 'REINFORCED_CONCRETE';
}
