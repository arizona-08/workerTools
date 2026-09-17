<?php

use App\StructuralCalculation\Beams\BeamBendingMoment;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamGoverningVerificationResult;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementSource;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamRequiredTensionReinforcementResult;
use App\StructuralCalculation\Beams\BeamResultSummaryBuilder;
use App\StructuralCalculation\Beams\BeamVerificationAggregationResult;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;

function summaryBuilder(): BeamResultSummaryBuilder
{
    return app(BeamResultSummaryBuilder::class);
}
function summaryAggregation(BeamVerificationStatus $status = BeamVerificationStatus::COMPLIANT): BeamVerificationAggregationResult
{
    $c = new BeamVerificationComponent('FLEXURE', BeamVerificationStatus::COMPLIANT);

    return new BeamVerificationAggregationResult($status, $status, $status, $c, $c, $c, $c, $c, []);
}
function buildSummary(?BeamVerificationComponent $governing = null, BeamVerificationStatus $status = BeamVerificationStatus::COMPLIANT, BeamCalculationMode $mode = BeamCalculationMode::DESIGN)
{
    return summaryBuilder()->build(summaryAggregation($status), new BeamGoverningVerificationResult($governing, []), new BeamBendingMoment(0, 95.45859375, FundamentalUltimateCombinationExpression::EN1990_6_10, 'M'), new BeamEffectiveDepthResult($mode, 600, 40, 8, 12, LongitudinalBarDiameterSource::CANDIDATE, 54, 546), new BeamRequiredTensionReinforcementResult(0, 0, 0, 0, 0, 413.46), new BeamReinforcementProposalCandidate(4, 12, M_PI * 12 ** 2 / 4, 4 * M_PI * 12 ** 2 / 4, 413.46, 0, 1), $mode);
}

it('maps the compact reference summary from existing sources without rounding', function () {
    $summary = buildSummary(new BeamVerificationComponent('FLEXURE', BeamVerificationStatus::COMPLIANT, .913947260705));
    expect($summary->utilization)->toBe(.913947260705)->and($summary->governingVerificationType)->toBe('FLEXURE')->and($summary->designBendingMoment)->toBe(95.45859375)->and($summary->effectiveDepth)->toBe(546.0)->and($summary->requiredLongitudinalReinforcementArea)->toBe(413.46)->and($summary->longitudinalReinforcement->barCount)->toBe(4)->and($summary->longitudinalReinforcement->barDiameter)->toBe(12.0)->and($summary->longitudinalReinforcement->providedArea)->toBe(4 * M_PI * 12 ** 2 / 4)->and($summary->longitudinalReinforcement->source)->toBe(BeamLongitudinalReinforcementSource::PROPOSED)->and($summary->status)->toBe(BeamVerificationStatus::COMPLIANT);
});

it('keeps global non-conclusive status independent from a favorable governing ratio', function () {
    $summary = buildSummary(new BeamVerificationComponent('CRACK', BeamVerificationStatus::COMPLIANT, .75), BeamVerificationStatus::NOT_CHECKED);
    expect($summary->utilization)->toBe(.75)->and($summary->status)->toBe(BeamVerificationStatus::NOT_CHECKED);
});

it('keeps governing fields null when no comparable verification exists and reflects verification mode', function () {
    $summary = buildSummary(null, BeamVerificationStatus::NOT_COMPLIANT, BeamCalculationMode::VERIFICATION);
    expect($summary->utilization)->toBeNull()->and($summary->governingVerificationType)->toBeNull()->and($summary->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT)->and($summary->longitudinalReinforcement->source)->toBe(BeamLongitudinalReinforcementSource::PROVIDED);
});
