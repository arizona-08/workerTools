<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

/** Valide une action variable sans appliquer gammaQ ni facteur de combinaison. */
final class BeamVariableLoadFactory
{
    public function fromValues(mixed $category, mixed $characteristicLoad): BeamVariableLoad
    {
        if ($category === null) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::MISSING_VARIABLE_ACTION_CATEGORY);
        }
        if (! is_string($category) || ($resolvedCategory = VariableActionCategory::tryFrom($category)) === null) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::INVALID_VARIABLE_ACTION_CATEGORY);
        }
        if ($resolvedCategory !== VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::UNSUPPORTED_VARIABLE_ACTION_CATEGORY);
        }
        if ($characteristicLoad === null) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::MISSING_CHARACTERISTIC_VARIABLE_LOAD);
        }
        if (is_bool($characteristicLoad) || ! is_numeric($characteristicLoad)) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD);
        }

        $load = (float) $characteristicLoad;
        if (! is_finite($load) || $load < 0) {
            throw new BeamVariableLoadException(BeamVariableLoadRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD);
        }

        return new BeamVariableLoad($resolvedCategory, $load);
    }
}
