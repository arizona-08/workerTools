<?php

namespace App\StructuralCalculation\Beams;

/** Bornes configurées du générateur V1 ; elles ne sont pas des règles Eurocode. */
final readonly class BeamReinforcementProposalConfiguration
{
    public const SUPPORTED_MINIMUM_TENSION_BAR_COUNT = 2;

    public const SUPPORTED_MAXIMUM_TENSION_BAR_COUNT = 8;

    public function __construct(
        public int $minimumTensionBarCount = self::SUPPORTED_MINIMUM_TENSION_BAR_COUNT,
        public int $maximumTensionBarCount = self::SUPPORTED_MAXIMUM_TENSION_BAR_COUNT,
    ) {}
}
