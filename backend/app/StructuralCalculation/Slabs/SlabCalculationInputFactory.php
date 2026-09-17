<?php

namespace App\StructuralCalculation\Slabs;

final readonly class SlabCalculationInputFactory
{
    public function __construct(private SlabCalculationConfigurationFactory $configuration, private SlabGeometryFactory $geometry, private SlabMaterialsFactory $materials, private SlabSurfaceLoadsFactory $loads) {}

    public function fromPayload(array $payload): SlabCalculationInput
    {
        $configuration = $payload['configuration'] ?? [];
        $geometry = $payload['geometry'] ?? [];
        $materials = $payload['materials'] ?? [];
        $loads = $payload['loads'] ?? [];

        return new SlabCalculationInput(
            $this->configuration->fromValues($configuration['elementType'] ?? '', $configuration['slabType'] ?? '', $configuration['spanningSystem'] ?? '', $configuration['structuralSystem'] ?? '', $configuration['loadModel'] ?? '', $configuration['materialType'] ?? '', $configuration['designCodeProfile'] ?? '', $configuration['designSituation'] ?? ''),
            $this->geometry->fromInternalValues($geometry['effectiveSpan'] ?? null, $geometry['thickness'] ?? null),
            $this->materials->fromValues($materials['concreteClass'] ?? null, $materials['steelGrade'] ?? null, $materials['exposureClass'] ?? null),
            $this->loads->fromValues($loads['finishes'] ?? null, $loads['partitions'] ?? null, $loads['otherPermanent'] ?? null, $loads['imposedLoad'] ?? null),
        );
    }
}
