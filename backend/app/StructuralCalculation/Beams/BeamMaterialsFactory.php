<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Exposure\ExposureClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

final readonly class BeamMaterialsFactory
{
    public function __construct(
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private ExposureClassRepository $exposureClasses,
    ) {}

    public function fromValues(mixed $concreteClass, mixed $steelGrade, mixed $exposureClasses): BeamMaterials
    {
        if ($concreteClass === null) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::MISSING_CONCRETE_CLASS);
        }
        if (! is_string($concreteClass) || $this->concreteClasses->find($concreteClass) === null) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::INVALID_CONCRETE_CLASS);
        }
        if ($steelGrade === null) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::MISSING_STEEL_GRADE);
        }
        if (! is_string($steelGrade) || $this->steelGrades->find($steelGrade) === null) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::INVALID_STEEL_GRADE);
        }
        if (! is_array($exposureClasses) || $exposureClasses === []) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::MISSING_EXPOSURE_CLASSES);
        }

        $resolvedExposureClasses = [];
        foreach ($exposureClasses as $exposureClass) {
            if (! is_string($exposureClass) || $this->exposureClasses->find($exposureClass) === null) {
                throw new BeamMaterialsException(BeamMaterialsRejectionReason::INVALID_EXPOSURE_CLASS);
            }
            if (in_array($exposureClass, $resolvedExposureClasses, true)) {
                throw new BeamMaterialsException(BeamMaterialsRejectionReason::DUPLICATE_EXPOSURE_CLASS);
            }
            $resolvedExposureClasses[] = $exposureClass;
        }

        return new BeamMaterials(
            concreteClass: $this->concreteClasses->find($concreteClass)->strengthClass,
            steelGrade: $this->steelGrades->find($steelGrade)->grade,
            exposureClasses: array_map(fn (string $code) => $this->exposureClasses->find($code)->code, $resolvedExposureClasses),
        );
    }
}
