<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\MaterialType;

final readonly class SlabCalculationConfigurationValidator
{
    public function __construct(private FrenchEurocodeProfileRepository $profileRepository) {}

    public function validate(SlabCalculationConfiguration $configuration): void
    {
        $this->ensure($configuration->elementType === ElementType::SLAB, SlabConfigurationRejectionReason::UNSUPPORTED_ELEMENT_TYPE);
        $this->ensure($configuration->slabType === SlabType::SOLID, SlabConfigurationRejectionReason::UNSUPPORTED_SLAB_TYPE);
        $this->ensure($configuration->spanningSystem === SlabSpanningSystem::ONE_WAY, SlabConfigurationRejectionReason::UNSUPPORTED_SPANNING_SYSTEM);
        $this->ensure($configuration->structuralSystem === SlabStructuralSystem::SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES, SlabConfigurationRejectionReason::UNSUPPORTED_STRUCTURAL_SYSTEM);
        $this->ensure($configuration->loadModel === SlabLoadModel::VERTICAL_UNIFORMLY_DISTRIBUTED, SlabConfigurationRejectionReason::UNSUPPORTED_LOAD_MODEL);
        $this->ensure($configuration->materialType === MaterialType::REINFORCED_CONCRETE, SlabConfigurationRejectionReason::UNSUPPORTED_MATERIAL_TYPE);
        $this->ensure($configuration->designSituation === DesignSituation::PERSISTENT_TRANSIENT, SlabConfigurationRejectionReason::UNSUPPORTED_DESIGN_SITUATION);
        $this->ensure(
            $configuration->designCodeProfile === DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR
                && $this->profileRepository->find($configuration->designCodeProfile->value) !== null,
            SlabConfigurationRejectionReason::UNSUPPORTED_DESIGN_CODE_PROFILE,
        );
    }

    private function ensure(bool $condition, SlabConfigurationRejectionReason $reason): void
    {
        if (! $condition) {
            throw new SlabConfigurationException($reason);
        }
    }
}
