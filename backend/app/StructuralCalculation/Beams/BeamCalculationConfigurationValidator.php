<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;

final readonly class BeamCalculationConfigurationValidator
{
    public function __construct(private FrenchEurocodeProfileRepository $profileRepository) {}

    public function validate(BeamCalculationConfiguration $configuration): void
    {
        $this->ensure($configuration->elementType === BeamElementType::BEAM, BeamConfigurationRejectionReason::UNSUPPORTED_ELEMENT_TYPE);
        $this->ensure($configuration->materialType === BeamMaterialType::REINFORCED_CONCRETE, BeamConfigurationRejectionReason::UNSUPPORTED_MATERIAL_TYPE);
        $this->ensure($configuration->sectionType === BeamSectionType::RECTANGULAR, BeamConfigurationRejectionReason::UNSUPPORTED_SECTION_TYPE);
        $this->ensure($configuration->supportSystem === BeamSupportSystem::SIMPLY_SUPPORTED, BeamConfigurationRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM);
        $this->ensure($configuration->loadModel === BeamLoadModel::UNIFORMLY_DISTRIBUTED, BeamConfigurationRejectionReason::UNSUPPORTED_LOAD_MODEL);
        $this->ensure($configuration->designSituation === BeamDesignSituation::PERSISTENT_TRANSIENT, BeamConfigurationRejectionReason::UNSUPPORTED_DESIGN_SITUATION);
        $this->ensure(
            $configuration->designCodeProfile === DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR
                && $this->profileRepository->find($configuration->designCodeProfile->value) !== null,
            BeamConfigurationRejectionReason::UNSUPPORTED_DESIGN_CODE_PROFILE,
        );
    }

    private function ensure(bool $condition, BeamConfigurationRejectionReason $reason): void
    {
        if (! $condition) {
            throw new BeamConfigurationException($reason);
        }
    }
}
