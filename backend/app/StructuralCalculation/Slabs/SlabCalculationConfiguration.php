<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\ElementType;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;
use App\StructuralCalculation\MaterialType;

/** Configuration du périmètre Dalle MVP, sans donnée de calcul. */
final readonly class SlabCalculationConfiguration
{
    public function __construct(
        public ElementType $elementType,
        public SlabType $slabType,
        public SlabSpanningSystem $spanningSystem,
        public SlabStructuralSystem $structuralSystem,
        public SlabLoadModel $loadModel,
        public MaterialType $materialType,
        public DesignCodeProfileIdentifier $designCodeProfile,
        public DesignSituation $designSituation,
    ) {}

    public static function mvp(): self
    {
        return new self(
            elementType: ElementType::SLAB,
            slabType: SlabType::SOLID,
            spanningSystem: SlabSpanningSystem::ONE_WAY,
            structuralSystem: SlabStructuralSystem::SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES,
            loadModel: SlabLoadModel::VERTICAL_UNIFORMLY_DISTRIBUTED,
            materialType: MaterialType::REINFORCED_CONCRETE,
            designCodeProfile: DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR,
            designSituation: DesignSituation::PERSISTENT_TRANSIENT,
        );
    }
}
