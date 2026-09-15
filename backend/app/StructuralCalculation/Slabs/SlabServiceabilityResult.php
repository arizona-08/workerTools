<?php

namespace App\StructuralCalculation\Slabs;

/** Regroupe uniquement les contrôles ELS individuels SLAB-10, sans agrégation de conformité. */
final readonly class SlabServiceabilityResult
{
    public function __construct(
        public SlabCrackVerificationResult $crackVerification,
        public SlabDeflectionVerificationResult $deflectionVerification,
    ) {}
}
