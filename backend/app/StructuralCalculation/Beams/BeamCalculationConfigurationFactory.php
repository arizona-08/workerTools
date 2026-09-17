<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;

/** Traduit les identifiants externes sans remplacer une valeur demandée par un défaut. */
final class BeamCalculationConfigurationFactory
{
    public function fromValues(
        string $calculationMode,
        string $elementType,
        string $materialType,
        string $sectionType,
        string $supportSystem,
        string $loadModel,
        string $designCodeProfile,
        string $designSituation,
        ?string $submodule = null,
    ): BeamCalculationConfiguration {
        $mode = BeamCalculationMode::tryFrom($calculationMode);
        $element = ElementType::tryFrom($elementType);
        $material = MaterialType::tryFrom($materialType);
        $section = BeamSectionType::tryFrom($sectionType);
        $support = BeamSupportSystem::tryFrom($supportSystem);
        $load = BeamLoadModel::tryFrom($loadModel);
        $profile = DesignCodeProfileIdentifier::tryFrom($designCodeProfile);
        $situation = DesignSituation::tryFrom($designSituation);
        $beamSubmodule = $submodule === null
            ? BeamSubmodule::BEAM_SIMPLE_RECTANGULAR
            : BeamSubmodule::tryFrom($submodule);

        if ($mode === null || $element === null || $material === null || $section === null || $support === null || $load === null || $profile === null || $situation === null || $beamSubmodule === null) {
            throw new BeamConfigurationException(BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE);
        }

        return new BeamCalculationConfiguration($mode, $element, $material, $section, $support, $load, $profile, $situation, $beamSubmodule);
    }
}
