<?php

namespace App\StructuralCalculation\Materials\ReinforcementSteel;

/**
 * Propriétés intrinsèques d'une armature passive.
 *
 * fyk et Es sont exprimés en MPa. fyd et gammaS n'appartiennent pas au
 * matériau : ils dépendront d'un profil normatif lors d'un futur calcul.
 */
final readonly class ReinforcementSteelProperties
{
    public function __construct(
        public ReinforcementSteelGrade $grade,
        public float $fyk,
        public float $es,
        public SteelDuctilityClass $ductilityClass,
    ) {}
}
