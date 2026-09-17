<?php

use App\StructuralCalculation\Beams\BeamCalculationDetails;
use App\StructuralCalculation\Beams\BeamGoverningVerificationResult;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcementSource;
use App\StructuralCalculation\Beams\BeamResultDetailsBuilder;
use App\StructuralCalculation\Beams\BeamResultSummary;
use App\StructuralCalculation\Beams\BeamResultSummaryReinforcement;
use App\StructuralCalculation\Beams\BeamVerificationAggregationResult;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;

it('assembles all detail sections from existing results without changing common summary values', function () {
    $component = new BeamVerificationComponent('FLEXURE', BeamVerificationStatus::COMPLIANT, .913947260705);
    $aggregation = new BeamVerificationAggregationResult(BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, $component, $component, $component, $component, $component, ['PROFILE_LIMITATION']);
    $summary = new BeamResultSummary(.913947260705, 'FLEXURE', 95.45859375, 546, 413.46, new BeamResultSummaryReinforcement(BeamLongitudinalReinforcementSource::PROPOSED, 4, 12, 452.3893421169302), BeamVerificationStatus::COMPLIANT);
    $details = app(BeamResultDetailsBuilder::class)->build($aggregation, new BeamGoverningVerificationResult($component, []), $summary, ['elementType' => 'BEAM', 'crossSectionType' => 'RECTANGULAR', 'structuralSystem' => 'SIMPLY_SUPPORTED'], ['ultimate' => ['wEd' => 18.075]], ['MEd' => 95.45859375, 'VEd' => 58.74375], ['requiredArea' => 413.46], ['providedArea' => 452.3893421169302], ['maximumResistance' => ['utilization' => .101266065264]], ['stress' => ['status' => 'COMPLIANT'], 'crack' => ['crackWidth' => .242950311652401], 'deflection' => ['method' => 'SIMPLIFIED_SPAN_DEPTH'], 'warnings' => ['SIMPLIFIED_METHOD_ONLY']]);
    expect($details)->toBeInstanceOf(BeamCalculationDetails::class)->and($details->assumptions['elementType'])->toBe('BEAM')->and($details->combinations['ultimate']['wEd'])->toBe(18.075)->and($details->internalForces['MEd'])->toBe($summary->designBendingMoment)->and($details->flexure['effectiveDepth'])->toBe($summary->effectiveDepth)->and($details->reinforcement['requiredArea'])->toBe($summary->requiredLongitudinalReinforcementArea)->and($details->reinforcement['longitudinalReinforcement'])->toBe($summary->longitudinalReinforcement)->and($details->governingVerification)->toBe($component)->and($details->warnings)->toBe(['PROFILE_LIMITATION', 'SIMPLIFIED_METHOD_ONLY']);
});

it('preserves the selected longitudinal-reinforcement source for design and verification', function (BeamLongitudinalReinforcementSource $source) {
    $component = new BeamVerificationComponent('FLEXURE', BeamVerificationStatus::COMPLIANT, .913947260705);
    $aggregation = new BeamVerificationAggregationResult(BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, $component, $component, $component, $component, $component, []);
    $reinforcement = new BeamResultSummaryReinforcement($source, 4, 12, 452.3893421169302);
    $summary = new BeamResultSummary(.913947260705, 'FLEXURE', 95.45859375, 546, 413.46, $reinforcement, BeamVerificationStatus::COMPLIANT);

    $details = app(BeamResultDetailsBuilder::class)->build($aggregation, new BeamGoverningVerificationResult($component, []), $summary, [], [], [], [], ['source' => $source, 'providedArea' => 452.3893421169302], [], []);

    expect($details->reinforcement['source'])->toBe($source)
        ->and($details->reinforcement['longitudinalReinforcement']->source)->toBe($source)
        ->and($details->reinforcement['providedArea'])->toBe(452.3893421169302);
})->with([
    'design proposal' => BeamLongitudinalReinforcementSource::PROPOSED,
    'verification input' => BeamLongitudinalReinforcementSource::PROVIDED,
]);

it('keeps unavailable detail values and explicit statuses without inventing values', function () {
    $component = new BeamVerificationComponent('CRACK', BeamVerificationStatus::NOT_APPLICABLE);
    $aggregation = new BeamVerificationAggregationResult(BeamVerificationStatus::NOT_APPLICABLE, BeamVerificationStatus::NOT_APPLICABLE, BeamVerificationStatus::NOT_APPLICABLE, $component, $component, $component, $component, $component, []);
    $summary = new BeamResultSummary(null, null, 95.45859375, 546, 413.46, new BeamResultSummaryReinforcement(BeamLongitudinalReinforcementSource::PROVIDED, 4, 12, 452.3893421169302), BeamVerificationStatus::NOT_APPLICABLE);

    $details = app(BeamResultDetailsBuilder::class)->build($aggregation, new BeamGoverningVerificationResult(null, []), $summary, [], [], ['MEd' => null, 'VEd' => null], ['domainStatus' => BeamVerificationStatus::NOT_APPLICABLE], ['compressionReinforcementArea' => null], [], ['deflection' => ['deflectionMm' => null, 'status' => BeamVerificationStatus::NOT_APPLICABLE]]);

    expect($details->overallStatus)->toBe(BeamVerificationStatus::NOT_APPLICABLE)
        ->and($details->governingVerification)->toBeNull()
        ->and($details->internalForces['MEd'])->toBeNull()
        ->and($details->reinforcement['compressionReinforcementArea'])->toBeNull()
        ->and($details->serviceability['deflection']['deflectionMm'])->toBeNull()
        ->and($details->serviceability['deflection']['status'])->toBe(BeamVerificationStatus::NOT_APPLICABLE);
});
