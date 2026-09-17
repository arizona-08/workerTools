<?php

namespace App\StructuralCalculation\Beams;

/** Catalogue produit central des sous-modules Poutre déclarés pour WorkerTools V1. */
final class BeamSubmoduleCatalog
{
    /** @return list<BeamSubmoduleCatalogEntry> */
    public function all(): array
    {
        return [
            new BeamSubmoduleCatalogEntry(
                BeamSubmodule::BEAM_SIMPLE_RECTANGULAR,
                'Poutre rectangulaire simplement appuyée',
                BeamSubmoduleStatus::AVAILABLE,
            ),
            new BeamSubmoduleCatalogEntry(
                BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR,
                'Poutre rectangulaire en console',
                BeamSubmoduleStatus::AVAILABLE,
            ),
        ];
    }

    public function find(string $identifier): ?BeamSubmoduleCatalogEntry
    {
        $submodule = BeamSubmodule::tryFrom($identifier);
        if ($submodule === null) {
            return null;
        }

        foreach ($this->all() as $entry) {
            if ($entry->id === $submodule) {
                return $entry;
            }
        }

        return null;
    }

    public function isAvailable(string $identifier): bool
    {
        return $this->find($identifier)?->status === BeamSubmoduleStatus::AVAILABLE;
    }
}
