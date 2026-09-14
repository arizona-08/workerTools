<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\StructuralClass;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\Exposure\ExposureClassRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;

/** Capacités réellement disponibles pour la chaîne de calcul Poutre MVP. */
final readonly class BeamCalculationCapabilities
{
    public function __construct(
        private ConcreteClassRepository $concreteClasses,
        private ReinforcementSteelGradeRepository $steelGrades,
        private ExposureClassRepository $exposureClasses,
        private FrenchEurocodeProfileRepository $profiles,
    ) {}

    /** @return list<string> */
    public function supportedConcreteClasses(): array
    {
        return array_map(fn ($concrete) => $concrete->strengthClass->value, $this->concreteClasses->all());
    }

    /** @return list<string> */
    public function supportedSteelGrades(): array
    {
        return array_map(fn ($steel) => $steel->grade->value, $this->steelGrades->all());
    }

    /** @return list<array{code: string, label: string}> */
    public function supportedExposureClasses(): array
    {
        $profile = $this->profiles->get();

        return array_values(array_filter(array_map(function (ExposureClassCode $code) use ($profile): ?array {
            // Une exposition doit être traitable à la fois par EC2-05 et BEAM-SLS-02.
            if ($profile->coverRequirements->minimumDurabilityCoverFor(StructuralClass::S4, $code) === null
                || $profile->beamCrackWidthRequirements->crackWidthLimitFor($code) === null) {
                return null;
            }
            $definition = $this->exposureClasses->get($code);

            return ['code' => $definition->code->value, 'label' => $definition->label];
        }, ExposureClassCode::cases())));
    }

    public function supportsConcreteClass(string $identifier): bool
    {
        return in_array($identifier, $this->supportedConcreteClasses(), true);
    }

    public function supportsSteelGrade(string $identifier): bool
    {
        return in_array($identifier, $this->supportedSteelGrades(), true);
    }

    public function supportsExposureClass(string $identifier): bool
    {
        return in_array($identifier, array_column($this->supportedExposureClasses(), 'code'), true);
    }

    /** Garantit que les références domaine sont exploitables par le calcul Poutre complet. */
    public function validate(BeamMaterials $materials): void
    {
        if (! $this->supportsConcreteClass($materials->concreteClass->value)) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::UNSUPPORTED_CONCRETE_CLASS);
        }
        if (! $this->supportsSteelGrade($materials->steelGrade->value)) {
            throw new BeamMaterialsException(BeamMaterialsRejectionReason::UNSUPPORTED_STEEL_GRADE);
        }
        foreach ($materials->exposureClasses as $exposureClass) {
            if (! $this->supportsExposureClass($exposureClass->value)) {
                throw new BeamMaterialsException(BeamMaterialsRejectionReason::UNSUPPORTED_EXPOSURE_CLASS);
            }
        }
    }
}
