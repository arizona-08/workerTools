import { Component, EventEmitter, Output, effect, inject, signal } from '@angular/core';
import { AbstractControl, FormControl, FormGroup, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { BeamCalculationConfigurationView, BeamCalculationMode, MVP_BEAM_CALCULATION_CONFIGURATION } from './beam-calculation-configuration';
import { BeamCalculationPayload, BeamGeometryPayload, BeamMaterialsPayload, BeamPermanentLoadsPayload, BeamVariableLoadPayload, buildBeamGeometryPayload } from './beam-geometry';
import { BeamLongitudinalReinforcementPayload, calculateProvidedSteelArea } from './beam-longitudinal-reinforcement';
import { BeamMaterialCatalogService } from './beam-material-catalog.service';
import { MVP_MATERIAL_DEFAULTS } from '../material-defaults';
import { nonNegativeFiniteNumberValidator } from '../non-negative-finite-number.validator';
import { positiveIntegerValidator } from './positive-integer.validator';
import { positiveFiniteNumberValidator } from '../positive-finite-number.validator';

@Component({
  selector: 'app-beam-form',
  imports: [ReactiveFormsModule],
  templateUrl: './beam-form.html',
})
export class BeamForm {
  @Output() readonly formChanged = new EventEmitter<void>();
  readonly materialCatalog = inject(BeamMaterialCatalogService);
  readonly configuration: BeamCalculationConfigurationView = { ...MVP_BEAM_CALCULATION_CONFIGURATION };
  readonly calculationMode = signal<BeamCalculationMode>(this.configuration.calculationMode);
  readonly geometryForm = new FormGroup({
    effectiveSpan: new FormControl<number | null>(null, [Validators.required, positiveFiniteNumberValidator]),
    width: new FormControl<number | null>(null, [Validators.required, positiveFiniteNumberValidator]),
    height: new FormControl<number | null>(null, [Validators.required, positiveFiniteNumberValidator]),
  });
  readonly materialsForm = new FormGroup({
    concreteClass: new FormControl<string>(MVP_MATERIAL_DEFAULTS.concreteClass, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.concreteClasses ?? [])] }),
    steelGrade: new FormControl<string>(MVP_MATERIAL_DEFAULTS.steelGrade, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.steelGrades ?? [])] }),
    exposureClass: new FormControl<string>(MVP_MATERIAL_DEFAULTS.exposureClass, { nonNullable: true, validators: [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.exposureClasses.map(({ code }) => code) ?? [])] }),
  });
  readonly permanentLoadsForm = new FormGroup({
    includeSelfWeight: new FormControl(true, { nonNullable: true }),
    additionalPermanentLoad: new FormControl(0, { nonNullable: true, validators: [Validators.required, nonNegativeFiniteNumberValidator] }),
  });
  readonly variableLoadForm = new FormGroup({
    category: new FormControl<'A'>('A', { nonNullable: true, validators: [(control) => control.value === 'A' ? null : { unsupportedVariableActionCategory: true }] }),
    characteristicLoad: new FormControl(0, { nonNullable: true, validators: [Validators.required, nonNegativeFiniteNumberValidator] }),
  });
  readonly longitudinalReinforcementForm = new FormGroup({
    tensionBarCount: new FormControl<number | null>(null, [Validators.required, positiveIntegerValidator]),
    tensionBarDiameter: new FormControl<number | null>(null, [Validators.required, (control) => this.catalogValueValidator(control, this.materialCatalog.catalog()?.reinforcementBarDiameters ?? [])]),
  });

  constructor() {
    this.materialCatalog.load();
    effect(() => {
      this.materialCatalog.catalog();
      this.materialsForm.updateValueAndValidity();
    });
    this.geometryForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.materialsForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.permanentLoadsForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.variableLoadForm.valueChanges.subscribe(() => this.formChanged.emit());
    this.longitudinalReinforcementForm.valueChanges.subscribe(() => this.formChanged.emit());
  }

  selectCalculationMode(mode: BeamCalculationMode): void {
    this.calculationMode.set(mode);
    this.configuration.calculationMode = mode;
    this.formChanged.emit();
  }

  geometryPayload(): BeamGeometryPayload | null {
    if (this.geometryForm.invalid) {
      return null;
    }

    return buildBeamGeometryPayload(this.geometryForm.getRawValue() as { effectiveSpan: number; width: number; height: number });
  }

  materialsPayload(): BeamMaterialsPayload | null {
    if (this.materialsForm.invalid) {
      return null;
    }

    const { concreteClass, steelGrade, exposureClass } = this.materialsForm.getRawValue();

    return { concreteClass, steelGrade, exposureClasses: [exposureClass] };
  }

  permanentLoadsPayload(): BeamPermanentLoadsPayload | null {
    if (this.permanentLoadsForm.invalid) {
      return null;
    }

    return { ...this.permanentLoadsForm.getRawValue(), unit: 'kN/m' };
  }

  variableLoadPayload(): BeamVariableLoadPayload | null {
    if (this.variableLoadForm.invalid) {
      return null;
    }

    return { ...this.variableLoadForm.getRawValue(), unit: 'kN/m' };
  }

  providedSteelArea(): number | null {
    if (this.longitudinalReinforcementForm.invalid) {
      return null;
    }

    const { tensionBarCount, tensionBarDiameter } = this.longitudinalReinforcementForm.getRawValue();

    return calculateProvidedSteelArea(tensionBarCount as number, tensionBarDiameter as number);
  }

  reinforcementPayload(): BeamLongitudinalReinforcementPayload | null | undefined {
    if (this.calculationMode() === 'DESIGN') {
      return undefined;
    }
    if (this.longitudinalReinforcementForm.invalid) {
      return null;
    }

    const { tensionBarCount, tensionBarDiameter } = this.longitudinalReinforcementForm.getRawValue();

    return {
      longitudinal: {
        tension: { barCount: tensionBarCount as number, barDiameter: tensionBarDiameter as number, diameterUnit: 'mm' },
      },
    };
  }

  payload(): BeamCalculationPayload | null {
    const geometry = this.geometryPayload();
    const materials = this.materialsPayload();
    const permanentLoads = this.permanentLoadsPayload();
    const variableLoad = this.variableLoadPayload();
    const reinforcement = this.reinforcementPayload();

    if (geometry === null || materials === null || permanentLoads === null || variableLoad === null || reinforcement === null) {
      return null;
    }

    return {
      configuration: this.configuration,
      geometry,
      materials,
      loads: { permanent: permanentLoads, variable: variableLoad },
      ...(reinforcement === undefined ? {} : { reinforcement }),
    };
  }

  isInputValid(): boolean {
    return this.materialCatalog.catalog() !== null && this.payload() !== null;
  }

  /** Valide toutes les sections avant qu'un parent déclenche l'appel au moteur. */
  requestPayload(): BeamCalculationPayload | null {
    this.geometryForm.markAllAsTouched();
    this.materialsForm.markAllAsTouched();
    this.permanentLoadsForm.markAllAsTouched();
    this.variableLoadForm.markAllAsTouched();
    if (this.calculationMode() === 'VERIFICATION') {
      this.longitudinalReinforcementForm.markAllAsTouched();
    }

    return this.payload();
  }

  private catalogValueValidator(control: AbstractControl, values: Array<string | number>): ValidationErrors | null {
    return values.length === 0 || values.includes(control.value) ? null : { unavailableCatalogValue: true };
  }

}
