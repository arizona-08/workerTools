<?php

namespace App\StructuralCalculation\CalculationNotes;

/**
 * Contrat commun de note de calcul. Les mappers Beam et Slab seront introduits
 * ultérieurement ; cette classe ne contient ni calcul, ni HTML, ni dépendance PDF.
 *
 * @param  list<CalculationNoteVerification>  $verifications
 * @param  list<CalculationNoteReinforcement>  $reinforcement
 * @param  list<string>  $warnings
 * @param  list<string>  $limitations
 */
final readonly class CalculationNoteDocument
{
    public function __construct(
        public CalculationNoteMetadata $metadata,
        public CalculationNoteSection $assumptions,
        public CalculationNoteSection $geometry,
        public CalculationNoteSection $materials,
        public CalculationNoteSection $loads,
        public ?CalculationNoteSection $combinations,
        public ?CalculationNoteSection $internalForces,
        public array $verifications,
        public array $reinforcement,
        public CalculationNoteFinalStatus $finalStatus,
        public array $warnings = [],
        public array $limitations = [],
    ) {}
}
