<?php

namespace App\StructuralCalculation\CalculationNotes\Pdf;

use App\StructuralCalculation\CalculationNotes\CalculationNoteDocument;

interface CalculationNoteRenderer
{
    public function render(CalculationNoteDocument $document): RenderedCalculationNote;
}
