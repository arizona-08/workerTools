<?php

test('the beam material catalog exposes identifiers without mechanical properties', function () {
    $response = $this->getJson('/api/beam/material-catalog');

    $response->assertOk()
        ->assertJsonPath('concreteClasses', ['C20/25', 'C25/30', 'C30/37'])
        ->assertJsonPath('steelGrades', ['B500B'])
        ->assertJsonPath('reinforcementBarDiameters', [8, 10, 12, 14, 16, 20, 25, 32])
        ->assertJsonPath('exposureClasses.1.code', 'XC1')
        ->assertJsonMissing(['fck', 'fcm', 'fctm', 'ecm', 'fyk', 'es', 'fcd', 'fyd']);
});
