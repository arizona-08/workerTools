import { Component, effect, inject, output } from '@angular/core';
import { AbstractControl, FormControl, FormGroup, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';

import { SUPPORTED_MATERIAL_DEFAULTS } from '../material-defaults';
import { nonNegativeFiniteNumberValidator } from '../non-negative-finite-number.validator';
import { positiveFiniteNumberValidator } from '../positive-finite-number.validator';
import { BeamMaterialCatalogService } from '../beam-form/beam-material-catalog.service';
import { SUPPORTED_SLAB_CALCULATION_CONFIGURATION } from './slab-calculation-configuration';
import { buildSlabGeometryPayload, SlabGeometryPayload } from './slab-geometry';
import { SlabSurfaceLoadsPayload } from './slab-surface-loads';

@Component({
  selector: 'app-slab-form',
  imports: [ReactiveFormsModule],
  templateUrl: './slab-form.html',
})
export class SlabForm {
  readonly formChanged = output<void>();
  /** Hypothèses explicites et non interactives du V1. */
  readonly configuration = SUPPORTED_SLAB_CALCULATION_CONFIGURATION;
  readonly materialCatalog = inject(BeamMaterialCatalogService);
  readonly geometryForm = new FormGroup({
    effectiveSpan: new FormControl<number | null>(null, [Validators.required, positiveFiniteNumberValidator]),
    thickness: new FormControl<number | null>(null, [Validators.required, positiveFiniteNumberValidator]),
  });
  readonly materialsForm = new FormGroup({
    concreteClass: new FormControl<string>(SUPPORTED_MATERIAL_DEFAULTS.concreteClass, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.concreteClasses ?? [])] }),
    steelGrade: new FormControl<string>(SUPPORTED_MATERIAL_DEFAULTS.steelGrade, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.steelGrades ?? [])] }),
    exposureClass: new FormControl<string>(SUPPORTED_MATERIAL_DEFAULTS.exposureClass, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.exposureClasses.map(({ code }) => code) ?? [])] }),
  });
  readonly surfaceLoadsForm = new FormGroup({
    finishes: new FormControl(0, { nonNullable: true, validators: [Validators.required, nonNegativeFiniteNumberValidator] }),
    partitions: new FormControl(0, { nonNullable: true, validators: [Validators.required, nonNegativeFiniteNumberValidator] }),
    otherPermanent: new FormControl(0, { nonNullable: true, validators: [Validators.required, nonNegativeFiniteNumberValidator] }),
    imposedLoad: new FormControl<number | null>(null, [Validators.required, nonNegativeFiniteNumberValidator]),
  });

  constructor() {
    this.materialCatalog.load();
    this.geometryForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.materialsForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.surfaceLoadsForm.valueChanges.subscribe(() => this.formChanged.emit());
    effect(() => {
      this.materialCatalog.catalog();
      this.materialsForm.updateValueAndValidity();
    });
  }

  geometryPayload(): SlabGeometryPayload | null {
    if (this.geometryForm.invalid) {
      return null;
    }

    return buildSlabGeometryPayload(this.geometryForm.getRawValue() as { effectiveSpan: number; thickness: number });
  }

  surfaceLoadsPayload(): SlabSurfaceLoadsPayload | null {
    if (this.surfaceLoadsForm.invalid) {
      return null;
    }

    const loads = this.surfaceLoadsForm.getRawValue() as { finishes: number; partitions: number; otherPermanent: number; imposedLoad: number };

    return { ...loads, unit: 'kN/m²' };
  }

  requestPayload(): object | null {
    const geometry = this.geometryPayload();
    const loads = this.surfaceLoadsPayload();
    if (geometry === null || loads === null || this.materialsForm.invalid) {
      return null;
    }

    return { configuration: this.configuration, geometry, materials: this.materialsForm.getRawValue(), loads };
  }

  private catalogValueValidator(control: AbstractControl, values: Array<string | number>): ValidationErrors | null {
    return values.length === 0 || values.includes(control.value) ? null : { unavailableCatalogValue: true };
  }
}
