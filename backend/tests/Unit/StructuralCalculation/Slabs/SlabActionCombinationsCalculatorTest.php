<?php

use App\StructuralCalculation\Beams\BeamCharacteristicActionsResult;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationCalculator;
use App\StructuralCalculation\Beams\BeamUltimateCombinationCalculator;
use App\StructuralCalculation\Beams\CharacteristicPermanentActions;
use App\StructuralCalculation\Beams\CharacteristicVariableAction;
use App\StructuralCalculation\Beams\SelfWeightResult;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;
use App\StructuralCalculation\Slabs\SlabActionCombinationsCalculator;
use App\StructuralCalculation\Slabs\SlabCalculationConfiguration;
use App\StructuralCalculation\Slabs\SlabCharacteristicActions;
use App\StructuralCalculation\Slabs\SlabCharacteristicActionsCalculator;
use App\StructuralCalculation\Slabs\SlabGeometry;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadCombinationType;
use App\StructuralCalculation\Slabs\SlabSurfaceLoads;

function slabActionCombinationsCalculator(): SlabActionCombinationsCalculator
{
    return app(SlabActionCombinationsCalculator::class);
}

function slabReferenceCharacteristicActions()
{
    return app(SlabCharacteristicActionsCalculator::class)->calculate(
        new SlabGeometry(5000, 200),
        new SlabSurfaceLoads(1.5, 1.0, 0.5, 2.0),
    );
}

it('combines SLAB-04 characteristic surface actions with French profile factors', function () {
    $result = slabActionCombinationsCalculator()->calculate(SlabCalculationConfiguration::supported(), slabReferenceCharacteristicActions());

    expect($result->uls->value)->toBe(13.8)
        ->and($result->slsCharacteristic->value)->toBe(10.0)
        ->and($result->slsFrequent->value)->toBe(9.0)
        ->and($result->slsQuasiPermanent->value)->toBe(8.6)
        ->and($result->uls->permanentFactor)->toBe(1.35)
        ->and($result->uls->variableFactor)->toBe(1.5)
        ->and($result->slsFrequent->variableFactor)->toBe(0.5)
        ->and($result->slsQuasiPermanent->variableFactor)->toBe(0.3)
        ->and($result->uls::UNIT)->toBe('kN/m²')
        ->and($result->uls->type)->toBe(SlabSurfaceLoadCombinationType::ULTIMATE)
        ->and($result->uls->expressionReference)->toBe(FundamentalUltimateCombinationExpression::EN1990_6_10)
        ->and($result->slsCharacteristic->expressionReference)->toBe(ServiceabilityCombinationExpression::EN1990_6_14)
        ->and($result->slsCharacteristic->variableFactor)->toBe(1.0)
        ->and($result->slsCharacteristic->value)->not->toBe(9.4);
});

it('keeps combinations independent from span and calculation strip width', function () {
    $loads = new SlabSurfaceLoads(1.5, 1.0, 0.5, 2.0);
    $calculator = app(SlabCharacteristicActionsCalculator::class);
    $first = slabActionCombinationsCalculator()->calculate(SlabCalculationConfiguration::supported(), $calculator->calculate(new SlabGeometry(3000, 200), $loads));
    $second = slabActionCombinationsCalculator()->calculate(SlabCalculationConfiguration::supported(), $calculator->calculate(new SlabGeometry(9000, 200), $loads));

    expect($first->uls->value)->toBe($second->uls->value)
        ->and($first->slsCharacteristic->value)->toBe($second->slsCharacteristic->value)
        ->and(property_exists($first, 'calculationStripWidth'))->toBeFalse()
        ->and(property_exists($first->uls, 'designLineLoad'))->toBeFalse()
        ->and(property_exists($first->uls, 'MEd'))->toBeFalse()
        ->and(property_exists($first->uls, 'VEd'))->toBeFalse();
});

it('keeps zero Gk or Qk mathematically explicit', function (float $gkTotal, float $qk, array $expected) {
    $actions = new SlabCharacteristicActions(200, 0.2, new ReinforcedConcreteUnitWeight(25), $gkTotal, 0, 0, 0, $gkTotal, $qk);
    $result = slabActionCombinationsCalculator()->calculate(SlabCalculationConfiguration::supported(), $actions);

    expect($result->uls->value)->toBe($expected['uls'])
        ->and($result->slsCharacteristic->value)->toBe($expected['characteristic'])
        ->and($result->slsFrequent->value)->toBe($expected['frequent'])
        ->and($result->slsQuasiPermanent->value)->toBe($expected['quasiPermanent']);
})->with([
    'zero Qk' => [8.0, 0.0, ['uls' => 10.8, 'characteristic' => 8.0, 'frequent' => 8.0, 'quasiPermanent' => 8.0]],
    'zero Gk' => [0.0, 2.0, ['uls' => 3.0, 'characteristic' => 2.0, 'frequent' => 1.0, 'quasiPermanent' => 0.6]],
]);

it('uses the exact same common combination engine as the Beam adapters', function () {
    $profile = app(FrenchEurocodeProfileRepository::class)->get();
    $beamActions = new BeamCharacteristicActionsResult(
        new CharacteristicPermanentActions(new SelfWeightResult(true, 300, 600, 0.18, new ReinforcedConcreteUnitWeight(25), 5), 3, 8),
        new CharacteristicVariableAction(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS, 2),
    );
    $slab = slabActionCombinationsCalculator()->calculate(SlabCalculationConfiguration::supported(), new SlabCharacteristicActions(200, 0.2, new ReinforcedConcreteUnitWeight(25), 5, 1.5, 1, 0.5, 8, 2));
    $beamUltimate = app(BeamUltimateCombinationCalculator::class)->calculate($beamActions, $profile);
    $beamServiceability = app(BeamServiceabilityCombinationCalculator::class)->calculate($beamActions, $profile);

    expect($slab->uls->value)->toBe($beamUltimate->designLineLoad)
        ->and($slab->slsCharacteristic->value)->toBe($beamServiceability->characteristic->resultingLineLoad)
        ->and($slab->slsFrequent->value)->toBe($beamServiceability->frequent->resultingLineLoad)
        ->and($slab->slsQuasiPermanent->value)->toBe($beamServiceability->quasiPermanent->resultingLineLoad);
});
