<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Units\LengthConverter;

/** Transforme les entrées surfaciques en Gk et Qk caractéristiques, sans combinaison. */
final readonly class SlabCharacteristicActionsCalculator
{
    public function __construct(
        private LengthConverter $lengthConverter,
        private ReinforcedConcreteUnitWeightRepository $unitWeights,
    ) {}

    public function calculate(
        SlabGeometry $geometry,
        SlabSurfaceLoads $loads,
    ): SlabCharacteristicActions {
        return $this->calculateWithUnitWeight($geometry, $loads, $this->unitWeights->normalWeightReinforcedConcrete());
    }

    /** Point d'extension explicite pour un futur profil de poids volumique compatible. */
    public function calculateWithUnitWeight(
        SlabGeometry $geometry,
        SlabSurfaceLoads $loads,
        ReinforcedConcreteUnitWeight $unitWeight,
    ): SlabCharacteristicActions {
        $this->ensurePositiveFinite($geometry->thickness, SlabCharacteristicActionsRejectionReason::INVALID_THICKNESS);
        $this->ensurePositiveFinite($unitWeight->value, SlabCharacteristicActionsRejectionReason::INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT);
        $this->ensureNonNegativeFinite($loads->finishes, SlabCharacteristicActionsRejectionReason::INVALID_FINISHES);
        $this->ensureNonNegativeFinite($loads->partitions, SlabCharacteristicActionsRejectionReason::INVALID_PARTITIONS);
        $this->ensureNonNegativeFinite($loads->otherPermanent, SlabCharacteristicActionsRejectionReason::INVALID_OTHER_PERMANENT);
        $this->ensureNonNegativeFinite($loads->imposedLoad, SlabCharacteristicActionsRejectionReason::INVALID_IMPOSED_LOAD);

        $thicknessMetres = $this->lengthConverter->millimetresToMetres($geometry->thickness);
        $selfWeight = $unitWeight->value * $thicknessMetres;

        return new SlabCharacteristicActions(
            thicknessMillimetres: $geometry->thickness,
            thicknessMetres: $thicknessMetres,
            unitWeight: $unitWeight,
            selfWeight: $selfWeight,
            finishes: $loads->finishes,
            partitions: $loads->partitions,
            otherPermanent: $loads->otherPermanent,
            permanentTotal: $selfWeight + $loads->finishes + $loads->partitions + $loads->otherPermanent,
            imposedLoad: $loads->imposedLoad,
        );
    }

    private function ensurePositiveFinite(float $value, SlabCharacteristicActionsRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new SlabCharacteristicActionsException($reason);
        }
    }

    private function ensureNonNegativeFinite(float $value, SlabCharacteristicActionsRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new SlabCharacteristicActionsException($reason);
        }
    }
}
