<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

/**
 * Catégories d'actions variables d'EN 1991-1-1.
 *
 * Le MVP ne couvre pour l'instant que la catégorie A. Les autres catégories
 * seront ajoutées avec leurs coefficients propres, jamais par défaut global.
 */
enum VariableActionCategory: string
{
    case A_DOMESTIC_RESIDENTIAL_AREAS = 'A';
}
