<?php

namespace App\StructuralCalculation\Beams;

/** Métadonnées produit d'un sous-module, distinctes de sa logique de calcul. */
final readonly class BeamSubmoduleCatalogEntry
{
    public function __construct(
        public BeamSubmodule $id,
        public string $label,
        public BeamSubmoduleStatus $status,
    ) {}

    public function supportSystem(): BeamSupportSystem
    {
        return $this->id->supportSystem();
    }

    /** @return array{id: string, label: string, status: string, supportSystem: string} */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id->value,
            'label' => $this->label,
            'status' => $this->status->value,
            'supportSystem' => $this->supportSystem()->value,
        ];
    }
}
