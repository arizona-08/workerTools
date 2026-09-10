<?php

namespace App\StructuralCalculation\Materials\Concrete;

/**
 * Propriétés intrinsèques du béton de masse volumique normale à 28 jours.
 *
 * Toutes les résistances et le module sont exprimés en MPa.
 * Les coefficients de calcul dépendants du profil normatif ne font pas partie
 * de cet objet matériau.
 */
final readonly class ConcreteProperties
{
    public function __construct(
        public ConcreteStrengthClass $strengthClass,
        public float $fck,
        public float $fcm,
        public float $fctm,
        public float $ecm,
    ) {}
}
