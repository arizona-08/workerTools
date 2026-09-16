<?php

function beamCalculationPayload(string $mode = 'DESIGN'): array
{
    return [
        'configuration' => [
            'calculationMode' => $mode,
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED',
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
        ...($mode === 'VERIFICATION' ? ['reinforcement' => ['longitudinal' => ['tension' => ['barCount' => 4, 'barDiameter' => 12, 'diameterUnit' => 'mm']]]] : []),
    ];
}

it('orchestrates the complete design calculation and exposes its existing result projections', function () {
    $response = $this->postJson('/api/beam/calculations', beamCalculationPayload());

    $response->assertOk()
        ->assertJsonPath('summary.status', 'NOT_COMPLIANT')
        ->assertJsonPath('summary.governingVerificationType', 'CRACK')
        ->assertJsonPath('summary.longitudinalReinforcement.barCount', 2)
        ->assertJsonPath('summary.longitudinalReinforcement.barDiameter', 16)
        ->assertJsonPath('details.assumptions.geometry.width', 300)
        ->assertJsonPath('details.assumptions.concreteClass', 'C30/37')
        ->assertJsonPath('details.assumptions.steelGrade', 'B500B')
        ->assertJsonPath('details.assumptions.exposureClass', 'XC1')
        ->assertJsonPath('details.serviceability.crack.status', 'NOT_COMPLIANT')
        ->assertJsonPath('details.combinations.characteristicActions.permanent.totalPermanentLoad', 9.5);

    expect(abs($response->json('details.assumptions.cover.cNom') - 20.0))->toBeLessThan(1e-12)
        ->and(abs($response->json('details.internalForces.bendingMoments.ultimate.maximumMoment') - 95.45859375))->toBeLessThan(1e-12)
        ->and(abs($response->json('details.internalForces.shearForces.ultimate.maximumAbsoluteShear') - 58.74375))->toBeLessThan(1e-12)
        ->and(abs($response->json('details.combinations.ultimate.designLineLoad') - 18.075))->toBeLessThan(1e-12)
        ->and($response->json('summary.effectiveDepth'))->toBe(564)
        ->and($response->json('summary.requiredLongitudinalReinforcementArea'))->toBeGreaterThan(399)
        ->and($response->json('summary.requiredLongitudinalReinforcementArea'))->toBeLessThan(400)
        ->and($response->json('summary.utilization'))->toBeGreaterThan(1.44)
        ->and($response->json('summary.utilization'))->toBeLessThan(1.45);
});

it('uses supplied longitudinal reinforcement in verification mode', function () {
    $response = $this->postJson('/api/beam/calculations', beamCalculationPayload('VERIFICATION'));

    $response->assertOk()
        ->assertJsonPath('summary.longitudinalReinforcement.source', 'PROVIDED')
        ->assertJsonPath('summary.longitudinalReinforcement.barCount', 4)
        ->assertJsonPath('summary.longitudinalReinforcement.barDiameter', 12);
});

it('returns a validation response for unsupported beam configuration', function () {
    $payload = beamCalculationPayload();
    $payload['configuration']['supportSystem'] = 'CONTINUOUS';

    $this->postJson('/api/beam/calculations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Cette configuration ou ce matériau n’est pas pris en charge par le calculateur actuel.')
        ->assertJsonPath('reason', 'UNSUPPORTED_SUPPORT_SYSTEM');
});

it('rejects an exposure class known by the domain but unsupported by the complete beam calculation', function () {
    $payload = beamCalculationPayload();
    $payload['materials']['exposureClasses'] = ['XC4'];

    $this->postJson('/api/beam/calculations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'La classe d’exposition sélectionnée est absente, invalide ou non prise en charge.')
        ->assertJsonPath('reason', 'UNSUPPORTED_EXPOSURE_CLASS');
});

it('uses the selected concrete class throughout the calculation details', function () {
    $payload = beamCalculationPayload();
    $payload['materials']['concreteClass'] = 'C25/30';

    $response = $this->postJson('/api/beam/calculations', $payload);

    $response->assertOk()
        ->assertJsonPath('details.assumptions.concreteClass', 'C25/30')
        ->assertJsonPath('details.flexure.designStrengths.concrete.concreteClass', 'C25/30');
});
