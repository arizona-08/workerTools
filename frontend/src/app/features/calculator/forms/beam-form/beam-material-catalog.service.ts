import { HttpClient } from '@angular/common/http';
import { Injectable, PLATFORM_ID, inject, signal } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { BeamSubmodule, BeamSupportSystem } from './beam-calculation-configuration';

export type BeamSubmoduleStatus = 'AVAILABLE' | 'COMING_SOON' | 'UNAVAILABLE';

export interface BeamSubmoduleCatalogEntry {
  id: BeamSubmodule;
  label: string;
  status: BeamSubmoduleStatus;
  supportSystem: BeamSupportSystem;
}

export interface BeamMaterialCatalog {
  concreteClasses: string[];
  steelGrades: string[];
  reinforcementBarDiameters: number[];
  exposureClasses: { code: string; label: string }[];
  beamSubmodules: BeamSubmoduleCatalogEntry[];
}

@Injectable({ providedIn: 'root' })
export class BeamMaterialCatalogService {
  private readonly http = inject(HttpClient, { optional: true });
  private readonly platformId = inject(PLATFORM_ID);
  readonly catalog = signal<BeamMaterialCatalog | null>(null);

  load(): void {
    if (!isPlatformBrowser(this.platformId)) {
      return;
    }

    this.http?.get<BeamMaterialCatalog>('/api/beam/material-catalog').subscribe({
      next: (catalog) => this.catalog.set(catalog),
      error: () => undefined,
    });
  }

  setCatalog(catalog: BeamMaterialCatalog): void {
    this.catalog.set(catalog);
  }
}
