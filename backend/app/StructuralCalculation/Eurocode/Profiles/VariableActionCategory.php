<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

/**
 * Catégories d'actions variables d'EN 1991-1-1.
 *
 * Le MVP Poutre ne couvre que la catégorie A. B à E sont connues afin que le
 * module puisse les refuser explicitement tant que le profil ne les couvre pas.
 */
enum VariableActionCategory: string
{
    case A_DOMESTIC_RESIDENTIAL_AREAS = 'A';
    case B_OFFICE_AREAS = 'B';
    case C_ASSEMBLY_AREAS = 'C';
    case D_SHOPPING_AREAS = 'D';
    case E_STORAGE_AND_INDUSTRIAL_AREAS = 'E';
}
