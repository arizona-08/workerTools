<?php

namespace App\StructuralCalculation\Beams;

/** Analyse instantanée élastique d'une section fissurée, sans fissuration ni fluage. */
final readonly class BeamServiceStressVerificationResult
{
    public const SECTION_MODEL = 'CRACKED_ELASTIC';

    public function __construct(
        public BeamServiceStressCheckStatus $status,
        public string $sectionModel,
        public ?float $modularRatio,
        public ?float $crackedNeutralAxisDepth,
        public ?float $crackedSecondMomentOfArea,
        public BeamServiceStressCheck $concreteCharacteristic,
        public BeamServiceStressCheck $steelCharacteristic,
        public BeamServiceStressCheck $concreteQuasiPermanent,
        public BeamServiceStressCheck $steelQuasiPermanent,
        public BeamServiceStressCheck $frequent,
        public ?string $governingStressCheck,
    ) {}
}
