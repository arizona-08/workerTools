<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

/** Énumère les candidats n × φ d'aire suffisante, sans contrôler leur disposition physique. */
final readonly class BeamReinforcementCandidatesGenerator
{
    public function __construct(
        private ReinforcementBarDiameterCatalog $diameters,
        private BeamReinforcementProposalConfiguration $configuration,
    ) {}

    public function generate(BeamRequiredReinforcementAreaResult $target): BeamReinforcementCandidatesResult
    {
        $this->ensureNonNegativeFinite($target->targetArea, BeamReinforcementCandidatesRejectionReason::INVALID_TARGET_AREA);
        $this->ensureConfiguration();

        $catalogue = $this->diameters->all();
        $candidates = [];
        foreach ($catalogue as $diameter) {
            for ($barCount = $this->configuration->minimumTensionBarCount; $barCount <= $this->configuration->maximumTensionBarCount; $barCount++) {
                $reinforcement = new BeamLongitudinalReinforcement($barCount, $diameter);
                if ($reinforcement->providedSteelArea < $target->targetArea) {
                    continue;
                }

                $candidates[] = new BeamReinforcementProposalCandidate(
                    barCount: $barCount,
                    barDiameter: $diameter,
                    barArea: $reinforcement->providedSteelArea / $barCount,
                    providedArea: $reinforcement->providedSteelArea,
                    targetArea: $target->targetArea,
                    excessArea: $reinforcement->providedSteelArea - $target->targetArea,
                    utilizationRatio: $target->targetArea / $reinforcement->providedSteelArea,
                );
            }
        }

        usort($candidates, fn (BeamReinforcementProposalCandidate $left, BeamReinforcementProposalCandidate $right): int => $left->excessArea <=> $right->excessArea
            ?: $left->barCount <=> $right->barCount
            ?: $left->barDiameter <=> $right->barDiameter);

        return new BeamReinforcementCandidatesResult(
            targetArea: $target->targetArea,
            catalogueUsed: $catalogue,
            minimumBarCount: $this->configuration->minimumTensionBarCount,
            maximumBarCount: $this->configuration->maximumTensionBarCount,
            status: $candidates === [] ? BeamReinforcementCandidatesStatus::NO_REINFORCEMENT_CANDIDATE : BeamReinforcementCandidatesStatus::CANDIDATES_AVAILABLE,
            candidates: $candidates,
            candidateCount: count($candidates),
        );
    }

    private function ensureConfiguration(): void
    {
        if ($this->configuration->minimumTensionBarCount < BeamReinforcementProposalConfiguration::MVP_MINIMUM_TENSION_BAR_COUNT) {
            throw new BeamReinforcementCandidatesException(BeamReinforcementCandidatesRejectionReason::INVALID_MINIMUM_TENSION_BAR_COUNT);
        }
        if ($this->configuration->maximumTensionBarCount < $this->configuration->minimumTensionBarCount
            || $this->configuration->maximumTensionBarCount > BeamReinforcementProposalConfiguration::MVP_MAXIMUM_TENSION_BAR_COUNT) {
            throw new BeamReinforcementCandidatesException(BeamReinforcementCandidatesRejectionReason::INVALID_MAXIMUM_TENSION_BAR_COUNT);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamReinforcementCandidatesRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamReinforcementCandidatesException($reason);
        }
    }
}
