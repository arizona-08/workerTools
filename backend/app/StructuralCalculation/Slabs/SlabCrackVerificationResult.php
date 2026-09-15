<?php

namespace App\StructuralCalculation\Slabs;

/** Vérification locale wk de la bande porteuse de 1 m ; ce résultat n'est pas une conformité de dalle. */
final readonly class SlabCrackVerificationResult
{
    public const METHOD = 'DIRECT_CRACK_WIDTH';

    public function __construct(
        public SlabCrackVerificationStatus $status,
        public string $method,
        public ?string $loadCombination,
        public ?float $serviceMoment,
        public ?float $modularRatio,
        public ?float $crackedNeutralAxisDepth,
        public ?float $crackedSecondMomentOfArea,
        public ?float $steelStress,
        public ?float $effectiveTensionArea,
        public ?float $effectiveReinforcementRatio,
        public ?float $maximumCrackSpacing,
        public ?float $strainDifference,
        public ?float $crackWidth,
        public ?float $crackWidthLimit,
        public ?float $utilization,
        public array $warnings = [],
    ) {}
}
