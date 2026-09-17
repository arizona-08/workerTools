<?php

namespace App\StructuralCalculation\CalculationNotes\Pdf;

use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\CalculationNotes\CalculationNoteDisplayValue;
use App\StructuralCalculation\CalculationNotes\CalculationNoteReinforcement;
use App\StructuralCalculation\CalculationNotes\CalculationNoteValue;
use App\StructuralCalculation\ElementType;

/** Formatage de présentation PDF exclusivement ; aucune donnée métier n’est modifiée. */
final class CalculationNotePdfPresentation
{
    public function calculationType(ElementType $type): string
    {
        return $type === ElementType::BEAM ? 'Poutre en béton armé' : 'Dalle en béton armé';
    }

    public function status(BeamVerificationStatus $status): string
    {
        return match ($status) {
            BeamVerificationStatus::COMPLIANT => 'Conforme',
            BeamVerificationStatus::NOT_COMPLIANT => 'Non conforme',
            BeamVerificationStatus::NOT_CHECKED => 'Non vérifié',
            BeamVerificationStatus::NOT_APPLICABLE => 'Non applicable',
            BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED => 'Méthode non prise en charge',
        };
    }

    public function value(CalculationNoteValue $value): string
    {
        $formatted = $value->displayValue ?? $this->scalar($value->value);

        return $value->unit === null || $formatted === '—' ? $formatted : "$formatted {$value->unit}";
    }

    public function scalar(string|int|float|bool|null $value): string
    {
        if ($value === null) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        if (is_float($value)) {
            return rtrim(rtrim(number_format($value, 3, ',', ' '), '0'), ',');
        }

        return (string) $value;
    }

    public function identifier(string $value): string
    {
        return CalculationNoteDisplayValue::french($value) ?? $value;
    }

    public function reinforcementDesignation(CalculationNoteReinforcement $reinforcement): string
    {
        if ($reinforcement->designation !== null) {
            return $reinforcement->designation;
        }

        $prefix = $reinforcement->count === null ? 'HA' : "{$reinforcement->count} HA";
        $diameter = $reinforcement->diameter === null ? '—' : $this->scalar($reinforcement->diameter);
        $spacing = $reinforcement->spacing === null ? '' : ' / '.$this->scalar($reinforcement->spacing).' mm';

        return $prefix.$diameter.$spacing;
    }
}
