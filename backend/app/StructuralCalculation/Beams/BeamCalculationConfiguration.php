<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;

/** Configuration métier, sans géométrie, charges ni calcul structurel. */
final readonly class BeamCalculationConfiguration
{
    public function __construct(
        public BeamCalculationMode $calculationMode,
        public BeamElementType $elementType,
        public BeamMaterialType $materialType,
        public BeamSectionType $sectionType,
        public BeamSupportSystem $supportSystem,
        public BeamLoadModel $loadModel,
        public DesignCodeProfileIdentifier $designCodeProfile,
        public BeamDesignSituation $designSituation,
    ) {}

    public static function mvp(): self
    {
        return new self(
            calculationMode: BeamCalculationMode::DESIGN,
            elementType: BeamElementType::BEAM,
            materialType: BeamMaterialType::REINFORCED_CONCRETE,
            sectionType: BeamSectionType::RECTANGULAR,
            supportSystem: BeamSupportSystem::SIMPLY_SUPPORTED,
            loadModel: BeamLoadModel::UNIFORMLY_DISTRIBUTED,
            designCodeProfile: DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR,
            designSituation: BeamDesignSituation::PERSISTENT_TRANSIENT,
        );
    }
}
