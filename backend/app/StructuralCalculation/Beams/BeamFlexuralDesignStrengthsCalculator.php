<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Concrete\ConcreteDesignStrengthCalculator;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSteelDesignStrengthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

/** Assemble les résistances EC2-03 des matériaux choisis, sans équation de section. */
final readonly class BeamFlexuralDesignStrengthsCalculator
{
    public function __construct(
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private ConcreteDesignStrengthCalculator $concreteDesignStrengthCalculator,
        private ReinforcementSteelDesignStrengthCalculator $steelDesignStrengthCalculator,
    ) {}

    public function calculate(BeamMaterials $materials, DesignCodeProfile $profile): BeamFlexuralDesignStrengthsResult
    {
        $concrete = $this->concreteClasses->get($materials->concreteClass);
        $steel = $this->steelGrades->get($materials->steelGrade);
        $factors = $profile->materialSafetyFactors;

        $this->ensurePositiveFinite($concrete->fck, BeamFlexuralDesignStrengthsRejectionReason::INVALID_CHARACTERISTIC_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($steel->fyk, BeamFlexuralDesignStrengthsRejectionReason::INVALID_CHARACTERISTIC_STEEL_STRENGTH);
        $this->ensurePositiveFinite($factors->alphaCc, BeamFlexuralDesignStrengthsRejectionReason::INVALID_CONCRETE_ALPHA_COEFFICIENT);
        $this->ensurePositiveFinite($factors->gammaC, BeamFlexuralDesignStrengthsRejectionReason::INVALID_CONCRETE_PARTIAL_FACTOR);
        $this->ensurePositiveFinite($factors->gammaS, BeamFlexuralDesignStrengthsRejectionReason::INVALID_STEEL_PARTIAL_FACTOR);

        $fcd = $this->concreteDesignStrengthCalculator->calculate($concrete, $profile);
        $fyd = $this->steelDesignStrengthCalculator->calculate($steel, $profile);
        $this->ensurePositiveFinite($fcd, BeamFlexuralDesignStrengthsRejectionReason::INVALID_DESIGN_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($fyd, BeamFlexuralDesignStrengthsRejectionReason::INVALID_DESIGN_STEEL_STRENGTH);

        return new BeamFlexuralDesignStrengthsResult(
            concrete: new BeamFlexuralConcreteDesignStrength(
                concreteClass: $concrete->strengthClass,
                fck: $concrete->fck,
                alphaCc: $factors->alphaCc,
                gammaC: $factors->gammaC,
                fcd: $fcd,
            ),
            steel: new BeamFlexuralSteelDesignStrength(
                steelGrade: $steel->grade,
                fyk: $steel->fyk,
                gammaS: $factors->gammaS,
                fyd: $fyd,
            ),
        );
    }

    private function ensurePositiveFinite(float $value, BeamFlexuralDesignStrengthsRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamFlexuralDesignStrengthsException($reason);
        }
    }
}
