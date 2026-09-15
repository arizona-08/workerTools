<?php

use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;

/**
 * Valeurs de référence calculées hors WorkerTools : γRC=25 kN/m³, γG=1.35,
 * γQ=1.50, ψ1=0.5, ψ2=0.3, bande=1 m, M=wL²/8 et V=wL/2.
 * Les données matériau sont C30/37 : fck=30, fctm=2.9, Ecm=33 000 MPa,
 * fcd=20 MPa ; B500B : fyk=500 MPa, fyd=500/1.15 MPa, Es=200 000 MPa.
 */
function qaSlabPayload(float $span, float $thickness, float $finishes, float $partitions, float $otherPermanent, float $imposedLoad): array
{
    return [
        'configuration' => ['elementType' => 'SLAB', 'slabType' => 'SOLID', 'spanningSystem' => 'ONE_WAY', 'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES', 'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED', 'materialType' => 'REINFORCED_CONCRETE', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => $span, 'thickness' => $thickness],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => $finishes, 'partitions' => $partitions, 'otherPermanent' => $otherPermanent, 'imposedLoad' => $imposedLoad],
    ];
}

function qaSlabAssertClose(float $actual, float $expected, float $tolerance = 1e-9): void
{
    expect(abs($actual - $expected))->toBeLessThan($tolerance);
}

it('matches independently established full-pipeline slab references', function (array $input, array $expected) {
    $result = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload($input));
    $details = $result->details;
    $uls = $details->combinations['uls'];
    $characteristic = $details->combinations['slsCharacteristic'];
    $frequent = $details->combinations['slsFrequent'];
    $quasiPermanent = $details->combinations['slsQuasiPermanent'];
    $linearLoads = $details->internalForces['linearLoads'];
    $forces = $details->internalForces['internalForces'];
    $flexure = $details->flexure['final'];
    $main = $details->mainReinforcement['proposal']->proposal;
    $secondary = $details->secondaryReinforcement['proposal'];
    $crack = $details->serviceability['crack'];
    $deflection = $details->serviceability['deflection'];

    expect($details->assumptions['geometry']->effectiveSpan)->toBe($expected['spanMm'])
        ->and($details->assumptions['geometry']->thickness)->toBe($expected['thicknessMm'])
        ->and($details->assumptions['geometry']->calculationStripWidth)->toBe(1000.0)
        ->and($details->assumptions['materials']->concreteClass->value)->toBe('C30/37')
        ->and($details->assumptions['materials']->steelGrade->value)->toBe('B500B')
        ->and($details->assumptions['materials']->exposureClass->value)->toBe('XC1');

    // gk,self = Gk,total - finitions - cloisons - autres permanentes.
    qaSlabAssertClose($uls->permanentCharacteristicLoad - $expected['additionalPermanent'], $expected['selfWeight'], 1e-10);
    qaSlabAssertClose($uls->permanentCharacteristicLoad, $expected['gkTotal'], 1e-10);
    qaSlabAssertClose($uls->value, $expected['uls'], 1e-10);
    qaSlabAssertClose($characteristic->value, $expected['slsCharacteristic'], 1e-10);
    qaSlabAssertClose($frequent->value, $expected['slsFrequent'], 1e-10);
    qaSlabAssertClose($quasiPermanent->value, $expected['slsQuasi'], 1e-10);

    expect($linearLoads->uls->stripWidthMetres)->toBe(1.0)
        ->and($linearLoads->uls->surfaceLoad)->toBe($uls->value);
    qaSlabAssertClose($linearLoads->uls->lineLoad, $expected['uls'], 1e-10);
    qaSlabAssertClose($forces->uls->maximumMoment, $expected['med']);
    qaSlabAssertClose($forces->uls->maximumShear, $expected['ved']);
    qaSlabAssertClose($forces->slsCharacteristic->maximumMoment, $expected['mCharacteristic']);
    qaSlabAssertClose($forces->slsCharacteristic->maximumShear, $expected['vCharacteristic']);
    qaSlabAssertClose($forces->slsFrequent->maximumMoment, $expected['mFrequent']);
    qaSlabAssertClose($forces->slsFrequent->maximumShear, $expected['vFrequent']);
    qaSlabAssertClose($forces->slsQuasiPermanent->maximumMoment, $expected['mQuasi']);
    qaSlabAssertClose($forces->slsQuasiPermanent->maximumShear, $expected['vQuasi']);

    expect($flexure->concreteDesignStrength)->toBe(20.0)
        ->and($flexure->meanTensileConcreteStrength)->toBe(2.9)
        ->and($flexure->characteristicSteelStrength)->toBe(500.0);
    qaSlabAssertClose($flexure->steelDesignStrength, 500 / 1.15);
    qaSlabAssertClose($flexure->effectiveDepth->nominalCover, $expected['cnom']);
    qaSlabAssertClose($flexure->effectiveDepth->effectiveDepth, $expected['d']);
    qaSlabAssertClose($flexure->reducedMoment, $expected['mu']);
    qaSlabAssertClose($flexure->neutralAxisRatio, $expected['xi']);
    qaSlabAssertClose($flexure->neutralAxisDepth, $expected['x']);
    qaSlabAssertClose($flexure->leverArm, $expected['z']);
    qaSlabAssertClose($flexure->requiredReinforcementArea, $expected['asReq']);
    qaSlabAssertClose($flexure->minimumReinforcementArea, $expected['asMin']);
    qaSlabAssertClose($flexure->designReinforcementArea, $expected['asDesign']);

    expect($main)->not->toBeNull()
        ->and($main->barDiameter)->toBe($expected['mainDiameter'])
        ->and($main->spacing)->toBe($expected['mainSpacing'])
        ->and($main->recalculatedFlexure->effectiveDepth->preliminaryMainBarDiameter)->toBe($expected['mainDiameter']);
    qaSlabAssertClose($main->providedAreaPerMeter, $expected['mainProvided']);
    expect($main->providedAreaPerMeter)->toBeGreaterThanOrEqual($flexure->designReinforcementArea);

    expect($secondary->proposal)->not->toBeNull()
        ->and($secondary->secondaryReinforcementRatio)->toBe(0.20)
        ->and($secondary->proposal->barDiameter)->toBe($expected['secondaryDiameter'])
        ->and($secondary->proposal->spacing)->toBe($expected['secondarySpacing']);
    qaSlabAssertClose($secondary->minimumRequiredAreaPerMeter, $expected['secondaryMinimum']);
    qaSlabAssertClose($secondary->proposal->providedAreaPerMeter, $expected['secondaryProvided']);

    expect($crack->loadCombination)->toBe('QUASI_PERMANENT')
        ->and($crack->status->value)->toBe('COMPLIANT')
        ->and($deflection->status->value)->toBe('COMPLIANT')
        ->and($result->status->value)->toBe('COMPLIANT')
        ->and($result->summary->governingVerificationType)->toBe($expected['governing']);
    qaSlabAssertClose($crack->modularRatio, 200000 / 33000);
    qaSlabAssertClose($crack->crackedNeutralAxisDepth, $expected['crackX']);
    qaSlabAssertClose($crack->crackedSecondMomentOfArea, $expected['crackI'], 1e-4);
    qaSlabAssertClose($crack->steelStress, $expected['steelStress']);
    qaSlabAssertClose($crack->effectiveTensionArea, $expected['effectiveTensionArea']);
    qaSlabAssertClose($crack->effectiveReinforcementRatio, $expected['effectiveReinforcementRatio']);
    qaSlabAssertClose($crack->maximumCrackSpacing, $expected['crackSpacing']);
    qaSlabAssertClose($crack->strainDifference, $expected['strainDifference'], 1e-12);
    qaSlabAssertClose($crack->crackWidth, $expected['wk']);
    qaSlabAssertClose($crack->crackWidthLimit, 0.4);
    qaSlabAssertClose($deflection->actualSpanDepthRatio, $expected['actualSpanDepth']);
    qaSlabAssertClose($deflection->allowableSpanDepthRatio, $expected['allowableSpanDepth']);
    qaSlabAssertClose($result->summary->utilization, $expected['utilization']);

    expect(array_keys(get_object_vars($details)))->toBe(['overallStatus', 'ulsStatus', 'slsStatus', 'governingVerification', 'assumptions', 'combinations', 'internalForces', 'flexure', 'mainReinforcement', 'secondaryReinforcement', 'serviceability', 'warnings']);
})->with([
    'A — nominal 5 m, 200 mm' => [qaSlabPayload(5000, 200, 1.5, 1, 0.5, 2), ['spanMm' => 5000.0, 'thicknessMm' => 200.0, 'additionalPermanent' => 3.0, 'selfWeight' => 5.0, 'gkTotal' => 8.0, 'uls' => 13.8, 'slsCharacteristic' => 10.0, 'slsFrequent' => 9.0, 'slsQuasi' => 8.6, 'med' => 43.125, 'ved' => 34.5, 'mCharacteristic' => 31.25, 'vCharacteristic' => 25.0, 'mFrequent' => 28.125, 'vFrequent' => 22.5, 'mQuasi' => 26.875, 'vQuasi' => 21.5, 'cnom' => 24.0, 'd' => 169.0, 'mu' => 0.0754963061517454, 'xi' => 0.09823003890716289, 'x' => 16.60087657531053, 'z' => 162.3596493698758, 'asReq' => 610.912257971427, 'asMin' => 254.852, 'asDesign' => 610.912257971427, 'mainDiameter' => 14.0, 'mainSpacing' => 250.0, 'mainProvided' => 615.7521601035994, 'secondaryMinimum' => 123.15043202071989, 'secondaryDiameter' => 8.0, 'secondarySpacing' => 300.0, 'secondaryProvided' => 167.5516081914556, 'crackX' => 31.979308935924532, 'crackI' => 80965392.5446586, 'steelStress' => 275.6457218745715, 'effectiveTensionArea' => 56006.89702135849, 'effectiveReinforcementRatio' => 0.010994220227354846, 'crackSpacing' => 218.42689838329812, 'strainDifference' => 0.0008269371656237145, 'wk' => 0.18062532024506367, 'actualSpanDepth' => 29.585798816568047, 'allowableSpanDepth' => 30.167100299898486, 'utilization' => 0.9807306145585231, 'governing' => 'DEFLECTION']],
    'B — portée et épaisseur 6 m, 280 mm' => [qaSlabPayload(6000, 280, 1, 0.5, 0.5, 3), ['spanMm' => 6000.0, 'thicknessMm' => 280.0, 'additionalPermanent' => 2.0, 'selfWeight' => 7.0, 'gkTotal' => 9.0, 'uls' => 16.65, 'slsCharacteristic' => 12.0, 'slsFrequent' => 10.5, 'slsQuasi' => 9.9, 'med' => 74.925, 'ved' => 49.95, 'mCharacteristic' => 54.0, 'vCharacteristic' => 36.0, 'mFrequent' => 47.25, 'vFrequent' => 31.5, 'mQuasi' => 44.55, 'vQuasi' => 29.7, 'cnom' => 22.0, 'd' => 252.0, 'mu' => 0.05899234693877551, 'xi' => 0.0760541256870799, 'x' => 19.165639673144135, 'z' => 244.33374413074233, 'asReq' => 705.2955399717036, 'asMin' => 380.016, 'asDesign' => 705.2955399717036, 'mainDiameter' => 12.0, 'mainSpacing' => 150.0, 'mainProvided' => 753.9822368615504, 'secondaryMinimum' => 150.7964473723101, 'secondaryDiameter' => 8.0, 'secondarySpacing' => 300.0, 'secondaryProvided' => 167.5516081914556, 'crackX' => 43.6378184246953, 'crackI' => 226087039.29413047, 'steelStress' => 248.83243728156873, 'effectiveTensionArea' => 70000.0, 'effectiveReinforcementRatio' => 0.010771174812307863, 'crackSpacing' => 307.2708360478961, 'strainDifference' => 0.0007464973118447061, 'wk' => 0.22937685311802988, 'actualSpanDepth' => 23.80952380952381, 'allowableSpanDepth' => 46.48906410181772, 'utilization' => 0.5734421327950747, 'governing' => 'CRACK']],
    'C — permanentes dominantes 4 m, 250 mm' => [qaSlabPayload(4000, 250, 3, 1.5, 1, 1), ['spanMm' => 4000.0, 'thicknessMm' => 250.0, 'additionalPermanent' => 5.5, 'selfWeight' => 6.25, 'gkTotal' => 11.75, 'uls' => 17.3625, 'slsCharacteristic' => 12.75, 'slsFrequent' => 12.25, 'slsQuasi' => 12.05, 'med' => 34.725, 'ved' => 34.725, 'mCharacteristic' => 25.5, 'vCharacteristic' => 25.5, 'mFrequent' => 24.5, 'vFrequent' => 24.5, 'mQuasi' => 24.1, 'vQuasi' => 24.1, 'cnom' => 22.0, 'd' => 222.0, 'mu' => 0.03522948624299976, 'xi' => 0.044841149270924696, 'x' => 9.954735138145283, 'z' => 218.01810594474188, 'asReq' => 366.3342530837459, 'asMin' => 334.776, 'asDesign' => 366.3342530837459, 'mainDiameter' => 12.0, 'mainSpacing' => 300.0, 'mainProvided' => 376.9911184307752, 'secondaryMinimum' => 75.39822368615505, 'secondaryDiameter' => 8.0, 'secondarySpacing' => 300.0, 'secondaryProvided' => 167.5516081914556, 'crackX' => 29.64746355304133, 'crackI' => 93222687.03356364, 'steelStress' => 301.37650978267027, 'effectiveTensionArea' => 70000.0, 'effectiveReinforcementRatio' => 0.0053855874061539315, 'crackSpacing' => 286.4582973810463, 'strainDifference' => 0.0009041295293480108, 'wk' => 0.2589954055889579, 'actualSpanDepth' => 18.01801801801802, 'allowableSpanDepth' => 103.08901651963363, 'utilization' => 0.6474885139723947, 'governing' => 'CRACK']],
]);
