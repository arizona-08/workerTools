<?php

namespace App\StructuralCalculation\Eurocode\Cover;

final readonly class StructuralClassResult
{
    /** @param list<StructuralClassModifier> $modifiers */
    public function __construct(
        public StructuralClass $initialStructuralClass,
        public array $modifiers,
        public StructuralClass $finalStructuralClass,
    ) {}
}
