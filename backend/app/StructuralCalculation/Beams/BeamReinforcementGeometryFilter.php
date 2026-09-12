<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSpacingRequirements;

/** Filtre de largeur d'un unique lit de barres, sans recalcul structurel ni placement par coordonnées. */
final class BeamReinforcementGeometryFilter
{
    public function filter(
        BeamReinforcementCandidatesResult $candidates,
        BeamGeometry $geometry,
        CoverCalculationResult $cover,
        BeamFlexuralDetailingAssumptions $flexuralDetailing,
        BeamReinforcementDetailingAssumptions $reinforcementDetailing,
        ReinforcementSpacingRequirements $spacingRequirements,
    ): BeamReinforcementGeometryCandidatesResult {
        $this->ensurePositiveFinite($geometry->width, 'INVALID_SECTION_WIDTH');
        $this->ensureNonNegativeFinite($cover->cNom, 'INVALID_NOMINAL_COVER');
        $this->ensurePositiveFinite($flexuralDetailing->transverseReinforcementDiameter, 'INVALID_TRANSVERSE_REINFORCEMENT_DIAMETER');
        $this->ensureNonNegativeFinite($reinforcementDetailing->maximumAggregateSize, 'INVALID_MAXIMUM_AGGREGATE_SIZE');
        $this->ensurePositiveFinite($spacingRequirements->barDiameterFactor, 'INVALID_BAR_DIAMETER_FACTOR');
        $this->ensureNonNegativeFinite($spacingRequirements->aggregateSizeAllowance, 'INVALID_AGGREGATE_SIZE_ALLOWANCE');
        $this->ensurePositiveFinite($spacingRequirements->absoluteMinimumClearSpacing, 'INVALID_ABSOLUTE_MINIMUM_CLEAR_SPACING');

        $availableWidth = $geometry->width - 2 * ($cover->cNom + $flexuralDetailing->transverseReinforcementDiameter);
        $this->ensurePositiveFinite($availableWidth, 'NON_POSITIVE_AVAILABLE_WIDTH');

        $accepted = [];
        $rejected = [];
        foreach ($candidates->candidates as $candidate) {
            $check = $this->check($candidate, $availableWidth, $reinforcementDetailing->maximumAggregateSize, $spacingRequirements);
            if ($check->geometricallyAdmissible) {
                $accepted[] = $check;
            } else {
                $rejected[] = $check;
            }
        }

        return new BeamReinforcementGeometryCandidatesResult($availableWidth, $accepted, $rejected);
    }

    private function check(BeamReinforcementProposalCandidate $candidate, float $availableWidth, float $maximumAggregateSize, ReinforcementSpacingRequirements $requirements): BeamReinforcementGeometryCheckResult
    {
        $this->ensurePositiveBarCount($candidate->barCount);
        $this->ensurePositiveFinite($candidate->barDiameter, 'INVALID_BAR_DIAMETER');

        $diameterSpacing = $requirements->barDiameterFactor * $candidate->barDiameter;
        $aggregateSpacing = $maximumAggregateSize + $requirements->aggregateSizeAllowance;
        $absoluteSpacing = $requirements->absoluteMinimumClearSpacing;
        $minimumClearSpacing = max($diameterSpacing, $aggregateSpacing, $absoluteSpacing);
        $governing = $this->spacingGoverningCriterion($diameterSpacing, $aggregateSpacing, $absoluteSpacing, $minimumClearSpacing);
        $requiredWidth = $candidate->barCount * $candidate->barDiameter + ($candidate->barCount - 1) * $minimumClearSpacing;
        $remainingWidth = $availableWidth - $requiredWidth;
        $admissible = $remainingWidth >= 0;

        return new BeamReinforcementGeometryCheckResult(
            candidate: $candidate,
            availableWidth: $availableWidth,
            maximumAggregateSize: $maximumAggregateSize,
            minimumClearSpacing: $minimumClearSpacing,
            spacingGoverningCriterion: $governing,
            requiredWidth: $requiredWidth,
            remainingWidth: $remainingWidth,
            geometricallyAdmissible: $admissible,
            rejectionReason: $admissible ? null : BeamReinforcementGeometryRejectionReason::INSUFFICIENT_HORIZONTAL_SPACE,
        );
    }

    private function spacingGoverningCriterion(float $diameter, float $aggregate, float $absolute, float $maximum): BeamReinforcementSpacingGoverningCriterion
    {
        $matches = ($diameter === $maximum ? 1 : 0) + ($aggregate === $maximum ? 1 : 0) + ($absolute === $maximum ? 1 : 0);

        return match (true) {
            $matches > 1 => BeamReinforcementSpacingGoverningCriterion::TIE,
            $diameter === $maximum => BeamReinforcementSpacingGoverningCriterion::BAR_DIAMETER,
            $aggregate === $maximum => BeamReinforcementSpacingGoverningCriterion::AGGREGATE_SIZE,
            default => BeamReinforcementSpacingGoverningCriterion::ABSOLUTE_MINIMUM,
        };
    }

    private function ensureNonNegativeFinite(float $value, string $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamReinforcementGeometryRejection($reason);
        }
    }

    private function ensurePositiveFinite(float $value, string $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamReinforcementGeometryRejection($reason);
        }
    }

    private function ensurePositiveBarCount(int $barCount): void
    {
        if ($barCount < BeamReinforcementProposalConfiguration::MVP_MINIMUM_TENSION_BAR_COUNT) {
            throw new BeamReinforcementGeometryRejection('INVALID_TENSION_BAR_COUNT');
        }
    }
}
