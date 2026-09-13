<?php

namespace App\StructuralCalculation\Beams;

/** Organise les sections du détail ; aucune formule ni logique de statut n'est exécutée ici. */
final class BeamResultDetailsBuilder
{
    /** @param array<string, mixed> $assumptions @param array<string, mixed> $combinations @param array<string, mixed> $internalForces @param array<string, mixed> $flexure @param array<string, mixed> $reinforcement @param array<string, mixed> $shear @param array<string, mixed> $serviceability */
    public function build(BeamVerificationAggregationResult $aggregation, BeamGoverningVerificationResult $governing, BeamResultSummary $summary, array $assumptions, array $combinations, array $internalForces, array $flexure, array $reinforcement, array $shear, array $serviceability): BeamCalculationDetails
    {
        return new BeamCalculationDetails(
            $aggregation->overallStatus,
            $aggregation->ulsStatus,
            $aggregation->slsStatus,
            $governing->governingVerification,
            $assumptions,
            $combinations,
            $internalForces,
            ['effectiveDepth' => $summary->effectiveDepth] + $flexure,
            ['requiredArea' => $summary->requiredLongitudinalReinforcementArea, 'longitudinalReinforcement' => $summary->longitudinalReinforcement] + $reinforcement,
            $shear,
            $serviceability,
            array_values(array_unique([...$aggregation->warnings, ...($serviceability['warnings'] ?? [])])),
        );
    }
}
