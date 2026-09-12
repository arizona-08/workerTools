<?php

namespace App\StructuralCalculation\Beams;

/** Bornes configurées du générateur MVP ; elles ne sont pas des règles Eurocode. */
final readonly class BeamReinforcementProposalConfiguration
{
    public const MVP_MINIMUM_TENSION_BAR_COUNT = 2;

    public const MVP_MAXIMUM_TENSION_BAR_COUNT = 8;

    public function __construct(
        public int $minimumTensionBarCount = self::MVP_MINIMUM_TENSION_BAR_COUNT,
        public int $maximumTensionBarCount = self::MVP_MAXIMUM_TENSION_BAR_COUNT,
    ) {}
}
