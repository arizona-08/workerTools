<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;

/** Configuration métier, sans géométrie, charges ni calcul structurel. */
final readonly class BeamCalculationConfiguration
{
    public function __construct(
        public BeamCalculationMode $calculationMode,
        public ElementType $elementType,
        public MaterialType $materialType,
        public BeamSectionType $sectionType,
        public BeamSupportSystem $supportSystem,
        public BeamLoadModel $loadModel,
        public DesignCodeProfileIdentifier $designCodeProfile,
        public DesignSituation $designSituation,
        public BeamSubmodule $submodule = BeamSubmodule::BEAM_SIMPLE_RECTANGULAR,
    ) {}

    public static function supported(): self
    {
        return new self(
            calculationMode: BeamCalculationMode::DESIGN,
            elementType: ElementType::BEAM,
            materialType: MaterialType::REINFORCED_CONCRETE,
            sectionType: BeamSectionType::RECTANGULAR,
            supportSystem: BeamSupportSystem::SIMPLY_SUPPORTED,
            loadModel: BeamLoadModel::UNIFORMLY_DISTRIBUTED,
            designCodeProfile: DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR,
            designSituation: DesignSituation::PERSISTENT_TRANSIENT,
            submodule: BeamSubmodule::BEAM_SIMPLE_RECTANGULAR,
        );
    }

    public function tensionFace(): BeamTensionFace
    {
        return $this->submodule->tensionFace();
    }
}
