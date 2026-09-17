<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;

/** Traduit des identifiants externes sans présumer qu'ils appartiennent au V1. */
final class SlabCalculationConfigurationFactory
{
    public function fromValues(
        string $elementType,
        string $slabType,
        string $spanningSystem,
        string $structuralSystem,
        string $loadModel,
        string $materialType,
        string $designCodeProfile,
        string $designSituation,
    ): SlabCalculationConfiguration {
        $element = ElementType::tryFrom($elementType);
        $type = SlabType::tryFrom($slabType);
        $spanning = SlabSpanningSystem::tryFrom($spanningSystem);
        $structural = SlabStructuralSystem::tryFrom($structuralSystem);
        $load = SlabLoadModel::tryFrom($loadModel);
        $material = MaterialType::tryFrom($materialType);
        $profile = DesignCodeProfileIdentifier::tryFrom($designCodeProfile);
        $situation = DesignSituation::tryFrom($designSituation);

        if ($element === null || $type === null || $spanning === null || $structural === null || $load === null || $material === null || $profile === null || $situation === null) {
            throw new SlabConfigurationException(SlabConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE);
        }

        return new SlabCalculationConfiguration($element, $type, $spanning, $structural, $load, $material, $profile, $situation);
    }
}
