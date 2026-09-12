<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthCalculator;
use App\StructuralCalculation\Beams\BeamEffectiveDepthException;
use App\StructuralCalculation\Beams\BeamEffectiveDepthRejectionReason;
use App\StructuralCalculation\Beams\BeamFlexuralDetailingAssumptions;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLongitudinalReinforcement;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Cover\CoverCalculationInput;
use App\StructuralCalculation\Eurocode\Cover\CoverMode;
use App\StructuralCalculation\Eurocode\Cover\NominalCoverCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;

function beamEffectiveDepthCalculator(): BeamEffectiveDepthCalculator
{
    return app(BeamEffectiveDepthCalculator::class);
}

function flexuralManualCover(float $nominalCover)
{
    return app(NominalCoverCalculator::class)->calculate(
        new CoverCalculationInput(coverMode: CoverMode::MANUAL, manualNominalCover: $nominalCover),
        app(FrenchEurocodeProfileRepository::class)->get(),
    );
}

it('calculates effective depth from the actual verification bar diameter', function () {
    $result = beamEffectiveDepthCalculator()->calculate(
        BeamCalculationMode::VERIFICATION,
        new BeamGeometry(6500, 300, 600),
        flexuralManualCover(30),
        BeamFlexuralDetailingAssumptions::mvp(),
        new BeamLongitudinalReinforcement(4, 16),
    );

    expect($result->mode)->toBe(BeamCalculationMode::VERIFICATION)
        ->and($result->nominalCover)->toBe(30.0)
        ->and($result->transverseBarDiameter)->toBe(8.0)
        ->and($result->longitudinalBarDiameter)->toBe(16.0)
        ->and($result->longitudinalBarDiameterSource)->toBe(LongitudinalBarDiameterSource::USER)
        ->and($result->tensionSteelCentroidOffset)->toBe(46.0)
        ->and($result->effectiveDepth)->toBe(554.0)
        ->and($result::UNIT)->toBe('mm')
        ->and($result::FORMULA)->toBe('d = h - c_nom - φ_st - φ_long / 2');
});

it('uses the explicit configurable design bar diameter before reinforcement is selected', function () {
    $result = beamEffectiveDepthCalculator()->calculate(
        BeamCalculationMode::DESIGN,
        new BeamGeometry(6500, 300, 600),
        flexuralManualCover(30),
        BeamFlexuralDetailingAssumptions::mvp(),
    );

    expect($result->longitudinalBarDiameter)->toBe(16.0)
        ->and($result->longitudinalBarDiameterSource)->toBe(LongitudinalBarDiameterSource::CONFIG)
        ->and($result->tensionSteelCentroidOffset)->toBe(46.0)
        ->and($result->effectiveDepth)->toBe(554.0);
});

it('updates the verification effective depth when the supplied longitudinal diameter changes', function () {
    $result = beamEffectiveDepthCalculator()->calculate(
        BeamCalculationMode::VERIFICATION,
        new BeamGeometry(6500, 300, 600),
        flexuralManualCover(30),
        BeamFlexuralDetailingAssumptions::mvp(),
        new BeamLongitudinalReinforcement(4, 20),
    );

    expect($result->tensionSteelCentroidOffset)->toBe(48.0)
        ->and($result->effectiveDepth)->toBe(552.0);
});

it('uses an injected transverse detailing diameter instead of a calculator constant', function () {
    $result = beamEffectiveDepthCalculator()->calculate(
        BeamCalculationMode::DESIGN,
        new BeamGeometry(6500, 300, 600),
        flexuralManualCover(30),
        new BeamFlexuralDetailingAssumptions(transverseReinforcementDiameter: 10, designTensionBarDiameter: 16),
    );

    expect($result->transverseBarDiameter)->toBe(10.0)
        ->and($result->tensionSteelCentroidOffset)->toBe(48.0)
        ->and($result->effectiveDepth)->toBe(552.0);
});

it('rejects an impossible geometry that would produce a non-positive effective depth', function () {
    try {
        beamEffectiveDepthCalculator()->calculate(
            BeamCalculationMode::DESIGN,
            new BeamGeometry(6500, 300, 40),
            flexuralManualCover(30),
            BeamFlexuralDetailingAssumptions::mvp(),
        );
    } catch (BeamEffectiveDepthException $exception) {
        expect($exception->reason)->toBe(BeamEffectiveDepthRejectionReason::NON_POSITIVE_EFFECTIVE_DEPTH);

        return;
    }

    throw new RuntimeException('Expected the impossible flexural geometry to be rejected.');
});

it('uses the EC2-05 nominal cover result in an input-to-verification integration chain', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'VERIFICATION',
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED',
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC4']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
        'reinforcement' => [
            'longitudinal' => [
                'tension' => ['barCount' => 4, 'barDiameter' => 16, 'diameterUnit' => 'mm'],
            ],
        ],
    ]);
    $cover = app(NominalCoverCalculator::class)->calculate(
        new CoverCalculationInput(
            coverMode: CoverMode::AUTO,
            exposureClasses: $setup->materials->exposureClasses,
            concreteClass: $setup->materials->concreteClass,
            designWorkingLifeYears: 50,
            reinforcementDiameter: BeamFlexuralDetailingAssumptions::mvp()->transverseReinforcementDiameter,
        ),
        app(FrenchEurocodeProfileRepository::class)->get(),
    );
    $result = beamEffectiveDepthCalculator()->calculate(
        $setup->configuration->calculationMode,
        $setup->geometry,
        $cover,
        BeamFlexuralDetailingAssumptions::mvp(),
        $setup->longitudinalReinforcement,
    );

    expect($cover->unit)->toBe('mm')
        ->and($result->nominalCover)->toBe($cover->cNom)
        ->and($result->longitudinalBarDiameter)->toBe($setup->longitudinalReinforcement->tensionBarDiameter)
        ->and($result->effectiveDepth)->toBe(600 - $cover->cNom - 8 - 8);
});
