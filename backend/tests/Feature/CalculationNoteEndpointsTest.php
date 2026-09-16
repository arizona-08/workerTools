<?php

function calculationNoteBeamPayload(): array
{
    return [
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m']],
    ];
}

function calculationNoteSlabPayload(): array
{
    return [
        'configuration' => ['elementType' => 'SLAB', 'slabType' => 'SOLID', 'spanningSystem' => 'ONE_WAY', 'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES', 'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED', 'materialType' => 'REINFORCED_CONCRETE', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 5000, 'thickness' => 200],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => 1.5, 'partitions' => 1, 'otherPermanent' => 0.5, 'imposedLoad' => 2],
    ];
}

it('allows a guest to calculate a slab', function () {
    $this->postJson('/api/slab/calculations', calculationNoteSlabPayload())
        ->assertOk()
        ->assertJsonPath('summary.status', 'COMPLIANT');
});

it('allows a guest to generate a downloadable PDF note from beam inputs', function () {
    $response = $this->postJson('/api/beam/calculations/pdf', calculationNoteBeamPayload());

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename="note-calcul-poutre.pdf"');

    expect($response->getContent())->toStartWith('%PDF');
});

it('allows a guest to generate a downloadable PDF note from slab inputs', function () {
    $response = $this->postJson('/api/slab/calculations/pdf', calculationNoteSlabPayload());

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'attachment; filename="note-calcul-dalle.pdf"');

    expect($response->getContent())->toStartWith('%PDF');
});

it('returns a JSON validation message instead of a corrupt PDF for invalid inputs', function () {
    $this->postJson('/api/beam/calculations/pdf', ['configuration' => []])
        ->assertUnprocessable()
        ->assertJsonStructure(['message']);
});
