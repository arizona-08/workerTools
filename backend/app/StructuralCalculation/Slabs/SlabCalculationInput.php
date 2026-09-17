<?php

namespace App\StructuralCalculation\Slabs;

/** Agrégat d'entrée Dalle prêt pour les étapes suivantes, sans orchestration de calcul. */
final readonly class SlabCalculationInput
{
    public function __construct(
        public SlabCalculationConfiguration $configuration,
        public SlabGeometry $geometry,
        public SlabMaterials $materials,
        public SlabSurfaceLoads $loads,
    ) {}
}
