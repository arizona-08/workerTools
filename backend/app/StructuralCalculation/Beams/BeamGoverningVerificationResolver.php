<?php

namespace App\StructuralCalculation\Beams;

/** Sélectionne le maximum d'utilisations brutes du résultat BEAM-RESULT-01. */
final class BeamGoverningVerificationResolver
{
    public function resolve(BeamVerificationAggregationResult $aggregation): BeamGoverningVerificationResult
    {
        $components = [$aggregation->flexureVerification, $aggregation->shearVerification, $aggregation->stressVerification, $aggregation->crackVerification, $aggregation->deflectionVerification];
        $candidates = [];
        $exclusions = [];
        foreach ($components as $component) {
            if (! in_array($component->status, [BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::NOT_COMPLIANT], true)) {
                $exclusions[] = new BeamGoverningVerificationExclusion($component->identifier, $component->status->value);
            } elseif ($component->utilization === null || ! is_finite($component->utilization)) {
                $exclusions[] = new BeamGoverningVerificationExclusion($component->identifier, 'UTILIZATION_UNAVAILABLE');
            } else {
                $candidates[] = $component;
            }
        }
        usort($candidates, fn (BeamVerificationComponent $left, BeamVerificationComponent $right): int => $right->utilization <=> $left->utilization);

        return new BeamGoverningVerificationResult($candidates[0] ?? null, $exclusions);
    }
}
