<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamCalculationCapabilities;
use App\StructuralCalculation\Beams\BeamMaterialsException;
use App\StructuralCalculation\Beams\BeamMaterialsFactory;

/** Réutilise les repositories et capabilities matériaux du V1 sans les recopier. */
final readonly class SlabMaterialsFactory
{
    public function __construct(
        private BeamMaterialsFactory $beamMaterialsFactory,
        private BeamCalculationCapabilities $capabilities,
    ) {}

    public function fromValues(mixed $concreteClass, mixed $steelGrade, mixed $exposureClass): SlabMaterials
    {
        try {
            $materials = $this->beamMaterialsFactory->fromValues($concreteClass, $steelGrade, $exposureClass === null ? null : [$exposureClass]);
            $this->capabilities->validate($materials);
        } catch (BeamMaterialsException $exception) {
            throw new SlabMaterialsException(SlabMaterialsRejectionReason::fromBeamReason($exception->reason), previous: $exception);
        }

        return new SlabMaterials($materials->concreteClass, $materials->steelGrade, $materials->exposureClasses[0]);
    }
}
