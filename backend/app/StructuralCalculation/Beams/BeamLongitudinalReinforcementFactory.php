<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

/** Valide le ferraillage de vérification et recalcule toujours As,prov. */
final readonly class BeamLongitudinalReinforcementFactory
{
    public function __construct(private ReinforcementBarDiameterCatalog $diameters) {}

    public function fromValues(
        BeamCalculationMode $mode,
        mixed $tensionBarCount = null,
        mixed $tensionBarDiameter = null,
        mixed $tensionRebarLayers = 1,
    ): ?BeamLongitudinalReinforcement {
        if ($mode === BeamCalculationMode::DESIGN) {
            if ($tensionBarCount !== null || $tensionBarDiameter !== null || $tensionRebarLayers !== 1) {
                throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::REINFORCEMENT_NOT_ALLOWED_IN_DESIGN);
            }

            return null;
        }
        if ($tensionBarCount === null) {
            throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::MISSING_TENSION_BAR_COUNT);
        }
        if (is_bool($tensionBarCount) || ! is_numeric($tensionBarCount) || ! is_finite((float) $tensionBarCount) || (float) (int) $tensionBarCount !== (float) $tensionBarCount || (int) $tensionBarCount < 1) {
            throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_COUNT);
        }
        if ($tensionBarDiameter === null) {
            throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::MISSING_TENSION_BAR_DIAMETER);
        }
        if (is_bool($tensionBarDiameter) || ! is_numeric($tensionBarDiameter) || ! is_finite((float) $tensionBarDiameter) || ! $this->diameters->supports((float) $tensionBarDiameter)) {
            throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::INVALID_TENSION_BAR_DIAMETER);
        }
        if ($tensionRebarLayers !== 1) {
            throw new BeamLongitudinalReinforcementException(BeamLongitudinalReinforcementRejectionReason::UNSUPPORTED_TENSION_REBAR_LAYERS);
        }

        return new BeamLongitudinalReinforcement((int) $tensionBarCount, (float) $tensionBarDiameter);
    }
}
