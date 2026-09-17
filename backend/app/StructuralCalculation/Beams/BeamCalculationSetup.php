<?php

namespace App\StructuralCalculation\Beams;

/** Composition du futur payload de calcul ; aucun calcul n'est déclenché ici. */
final readonly class BeamCalculationSetup
{
    public function __construct(
        public BeamCalculationConfiguration $configuration,
        public BeamGeometry $geometry,
        public ?BeamMaterials $materials = null,
        public ?BeamPermanentLoads $permanentLoads = null,
        public ?BeamVariableLoad $variableLoad = null,
        public ?BeamLongitudinalReinforcement $longitudinalReinforcement = null,
    ) {}
}
