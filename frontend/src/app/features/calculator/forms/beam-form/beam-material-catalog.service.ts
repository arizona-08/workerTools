import { HttpClient } from '@angular/common/http';
import { Injectable, PLATFORM_ID, inject, signal } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';

export interface BeamMaterialCatalog {
  concreteClasses: string[];
  steelGrades: string[];
  reinforcementBarDiameters: number[];
  exposureClasses: { code: string; label: string }[];
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
