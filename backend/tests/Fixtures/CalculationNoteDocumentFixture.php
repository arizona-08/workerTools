<?php

namespace Tests\Fixtures;

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
use DateTimeImmutable;

/** Generic fixture for the renderer. It is deliberately not a Beam or Slab result mapper. */
final class CalculationNoteDocumentFixture
{
    public static function make(
        BeamVerificationStatus $status = BeamVerificationStatus::COMPLIANT,
        bool $withOptionalSections = true,
        int $extraGeometryItems = 0,
    ): CalculationNoteDocument {
        $geometryItems = [
            new CalculationNoteValue('width', 'Largeur', 300, 'mm'),
            new CalculationNoteValue('height', 'Hauteur', 600, 'mm'),
        ];

        for ($index = 1; $index <= $extraGeometryItems; $index++) {
            $geometryItems[] = new CalculationNoteValue("reference-$index", "Référence de contrôle $index", $index, 'mm');
        }

        return new CalculationNoteDocument(
            new CalculationNoteMetadata(
                'Note de calcul — exemple de rendu',
                ElementType::BEAM,
                new DateTimeImmutable('2026-09-16T10:00:00+02:00'),
                'NF EN 1992-1-1:2005 + NA française',
            ),
            new CalculationNoteSection('assumptions', 'Hypothèses et paramètres', [
                new CalculationNoteValue('supportSystem', 'Système statique', 'Simplement appuyé'),
                new CalculationNoteValue('included', 'Poids propre inclus', true),
            ]),
            new CalculationNoteSection('geometry', 'Géométrie', $geometryItems, [
                new CalculationNoteStep('Contrôle de forme', 'μ = MEd / (b · d² · fcd)', 'σ ≤ fyd ; φ γ ψ ξ ρ ε', 0.05376),
            ]),
            new CalculationNoteSection('materials', 'Matériaux', [
                new CalculationNoteValue('concrete', 'Classe de béton', 'C30/37'),
                new CalculationNoteValue('steel', 'Classe d’acier', 'B500B'),
            ]),
            new CalculationNoteSection('loads', 'Charges', [
                new CalculationNoteValue('permanent', 'Charge permanente', 9.5, 'kN/m'),
                new CalculationNoteValue('variable', 'Charge d’exploitation', 4, 'kN/m'),
            ]),
            $withOptionalSections ? new CalculationNoteSection('combinations', 'Combinaisons', [
                new CalculationNoteValue('elu', 'Combinaison ELU', 18.075, 'kN/m'),
            ]) : null,
            $withOptionalSections ? new CalculationNoteSection('internal-forces', 'Sollicitations', [
                new CalculationNoteValue('med', 'Moment ELU', 95.45859375, 'kN·m'),
            ]) : null,
            [
                new CalculationNoteVerification('FLEXURE', 'Flexion', $status, 0.75, 95.46, 120, 'kN·m', 'Section simplement armée', [
                    new CalculationNoteValue('lever-arm', 'Bras de levier', 500, 'mm'),
                ]),
            ],
            [
                new CalculationNoteReinforcement('LONGITUDINAL', 'Armatures longitudinales', '4 HA12', 12, 4, providedArea: 452.39, requiredArea: 413.46, unit: 'mm²'),
            ],
            new CalculationNoteFinalStatus($status, 'FLEXURE', 0.75),
            ['Le rendu est une projection documentaire ; il ne recalcule aucune valeur.'],
            ['Le périmètre de calcul couvert reste celui du moteur source.'],
        );
    }
}
