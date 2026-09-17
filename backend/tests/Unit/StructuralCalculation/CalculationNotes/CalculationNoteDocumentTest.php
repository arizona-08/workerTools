<?php

use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\CalculationNotes\CalculationNoteDocument;
use App\StructuralCalculation\CalculationNotes\CalculationNoteFinalStatus;
use App\StructuralCalculation\CalculationNotes\CalculationNoteMetadata;
use App\StructuralCalculation\CalculationNotes\CalculationNoteReinforcement;
use App\StructuralCalculation\CalculationNotes\CalculationNoteSection;
use App\StructuralCalculation\CalculationNotes\CalculationNoteStep;
use App\StructuralCalculation\CalculationNotes\CalculationNoteValue;
use App\StructuralCalculation\CalculationNotes\CalculationNoteVerification;
use App\StructuralCalculation\ElementType;

function noteMetadata(ElementType $type): CalculationNoteMetadata
{
    return new CalculationNoteMetadata(
        $type === ElementType::BEAM ? 'Note de calcul — Poutre en béton armé' : 'Note de calcul — Dalle unidirectionnelle en béton armé',
        $type,
        new DateTimeImmutable('2026-09-16T10:00:00+02:00'),
        'NF_EN_1992_1_1_2005_FR',
    );
}

function noteSection(string $key, string $title, array $items = []): CalculationNoteSection
{
    return new CalculationNoteSection($key, $title, $items);
}

it('represents a beam-shaped note without calculating its values again', function () {
    $document = new CalculationNoteDocument(
        noteMetadata(ElementType::BEAM),
        noteSection('assumptions', 'Hypothèses', [new CalculationNoteValue('supportSystem', 'Système statique', 'SIMPLY_SUPPORTED')]),
        noteSection('geometry', 'Géométrie', [new CalculationNoteValue('width', 'Largeur', 300, 'mm')]),
        noteSection('materials', 'Matériaux', [new CalculationNoteValue('concreteClass', 'Classe de béton', 'C30/37')]),
        noteSection('loads', 'Charges', [new CalculationNoteValue('GkTotal', 'Charge permanente totale', 9.5, 'kN/m')]),
        noteSection('combinations', 'Combinaisons', [new CalculationNoteValue('wEd', 'Charge ELU', 18.075, 'kN/m')]),
        noteSection('internalForces', 'Sollicitations', [new CalculationNoteValue('MEd', 'Moment ELU', 95.45859375, 'kN·m')]),
        [new CalculationNoteVerification('FLEXURE', 'Flexion', BeamVerificationStatus::COMPLIANT, 0.75, 95.45859375, 120, 'kN·m', details: [new CalculationNoteValue('z', 'Bras de levier', 500, 'mm')])],
        [
            new CalculationNoteReinforcement('LONGITUDINAL', 'Armatures longitudinales', '4 HA12', 12, 4, providedArea: 452.39, requiredArea: 413.46, unit: 'mm²'),
            new CalculationNoteReinforcement('STIRRUPS', 'Étriers', '2 HA8 / 150 mm', 8, 2, 150, unit: 'mm'),
        ],
        new CalculationNoteFinalStatus(BeamVerificationStatus::COMPLIANT, 'FLEXURE', 0.75, [new CalculationNoteValue('MEd', 'Moment ELU', 95.45859375, 'kN·m')]),
        ['Méthode simplifiée de déformation.'],
        ['Le périmètre V1 est limité.'],
    );

    expect($document->metadata->calculationType)->toBe(ElementType::BEAM)
        ->and($document->internalForces?->items[0]->value)->toBe(95.45859375)
        ->and($document->reinforcement[0]->designation)->toBe('4 HA12')
        ->and($document->reinforcement[0]->count)->toBe(4)
        ->and($document->reinforcement[0]->diameter)->toBe(12.0)
        ->and($document->warnings)->toBe(['Méthode simplifiée de déformation.'])
        ->and($document->limitations)->toBe(['Le périmètre V1 est limité.']);
});

it('represents a slab-shaped note with module-specific sections left absent when unavailable', function () {
    $document = new CalculationNoteDocument(
        noteMetadata(ElementType::SLAB),
        noteSection('assumptions', 'Hypothèses', [new CalculationNoteValue('calculationStripWidth', 'Bande de calcul', 1, 'm')]),
        noteSection('geometry', 'Géométrie', [new CalculationNoteValue('thickness', 'Épaisseur', 200, 'mm')]),
        noteSection('materials', 'Matériaux'),
        noteSection('loads', 'Charges', [new CalculationNoteValue('imposedLoad', 'Charge d’exploitation', 2, 'kN/m²')]),
        null,
        noteSection('internalForces', 'Sollicitations', [new CalculationNoteValue('MEd', 'Moment ELU', 12.5, 'kN·m/m')]),
        [new CalculationNoteVerification('DEFLECTION', 'Déformation', BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, null, method: 'SIMPLIFIED_SPAN_DEPTH')],
        [
            new CalculationNoteReinforcement('MAIN', 'Armatures principales', 'HA10 / 150 mm', 10, spacing: 150, providedArea: 523.6, requiredArea: 450, unit: 'mm²/m'),
            new CalculationNoteReinforcement('SECONDARY', 'Armatures secondaires', 'HA8 / 200 mm', 8, spacing: 200, providedArea: 251.3, unit: 'mm²/m'),
        ],
        new CalculationNoteFinalStatus(BeamVerificationStatus::NOT_CHECKED, 'DEFLECTION', null),
    );

    expect($document->metadata->calculationType)->toBe(ElementType::SLAB)
        ->and($document->combinations)->toBeNull()
        ->and($document->verifications[0]->utilization)->toBeNull()
        ->and($document->reinforcement[0]->spacing)->toBe(150.0)
        ->and($document->reinforcement[0]->providedArea)->toBe(523.6)
        ->and($document->finalStatus->status)->toBe(BeamVerificationStatus::NOT_CHECKED);
});

it('preserves every common verification status and structured formula step without rendering a PDF', function (BeamVerificationStatus $status) {
    $verification = new CalculationNoteVerification('CHECK', 'Vérification', $status, null);
    $step = new CalculationNoteStep('Moment réduit', 'μ = MEd / (b · d² · fcd)', 'μ = 95,46×10⁶ / (…) ', 0.05376);

    expect($verification->status)->toBe($status)
        ->and($verification->utilization)->toBeNull()
        ->and($step->result)->toBe(0.05376)
        ->and($step->formula)->toBe('μ = MEd / (b · d² · fcd)');
})->with(BeamVerificationStatus::cases());
