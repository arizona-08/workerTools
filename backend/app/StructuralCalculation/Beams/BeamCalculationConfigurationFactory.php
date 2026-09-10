<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;

/** Traduit les identifiants externes sans remplacer une valeur demandée par un défaut. */
final class BeamCalculationConfigurationFactory
{
    public function fromValues(
        string $elementType,
        string $materialType,
        string $sectionType,
        string $supportSystem,
        string $loadModel,
        string $designCodeProfile,
        string $designSituation,
    ): BeamCalculationConfiguration {
        $element = BeamElementType::tryFrom($elementType);
        $material = BeamMaterialType::tryFrom($materialType);
        $section = BeamSectionType::tryFrom($sectionType);
        $support = BeamSupportSystem::tryFrom($supportSystem);
        $load = BeamLoadModel::tryFrom($loadModel);
        $profile = DesignCodeProfileIdentifier::tryFrom($designCodeProfile);
        $situation = BeamDesignSituation::tryFrom($designSituation);

        if ($element === null || $material === null || $section === null || $support === null || $load === null || $profile === null || $situation === null) {
            throw new BeamConfigurationException(BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE);
        }

        return new BeamCalculationConfiguration($element, $material, $section, $support, $load, $profile, $situation);
    }
}
