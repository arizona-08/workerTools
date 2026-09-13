<?php

namespace App\StructuralCalculation\Beams;

/** Priorité commune : échec, conclusion incomplète, puis conformité. */
final class BeamVerificationStatusAggregator
{
    /** @param list<BeamVerificationComponent> $components */
    public function aggregate(array $components): BeamVerificationStatus
    {
        $statuses = array_map(fn (BeamVerificationComponent $component): BeamVerificationStatus => $component->status, $components);
        if (in_array(BeamVerificationStatus::NOT_COMPLIANT, $statuses, true)) {
            return BeamVerificationStatus::NOT_COMPLIANT;
        }
        if (in_array(BeamVerificationStatus::NOT_CHECKED, $statuses, true)
            || in_array(BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, $statuses, true)) {
            return BeamVerificationStatus::NOT_CHECKED;
        }

        return BeamVerificationStatus::COMPLIANT;
    }
}
