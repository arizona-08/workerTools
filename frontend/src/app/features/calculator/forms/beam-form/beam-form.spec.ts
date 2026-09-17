import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BeamForm } from './beam-form';
import { BeamMaterialCatalogService } from './beam-material-catalog.service';

describe('BeamForm', () => {
  let component: BeamForm;
  let fixture: ComponentFixture<BeamForm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BeamForm],
    }).compileComponents();

    TestBed.inject(BeamMaterialCatalogService).setCatalog({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'],
      steelGrades: ['B500B'],
      reinforcementBarDiameters: [8, 10, 12, 14, 16, 20, 25, 32],
      exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
      beamSubmodules: [
        { id: 'BEAM_SIMPLE_RECTANGULAR', label: 'Poutre rectangulaire simplement appuyée', status: 'AVAILABLE', supportSystem: 'SIMPLY_SUPPORTED' },
        { id: 'BEAM_CANTILEVER_RECTANGULAR', label: 'Poutre rectangulaire en console', status: 'AVAILABLE', supportSystem: 'CANTILEVER' },
      ],
    });

    fixture = TestBed.createComponent(BeamForm);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('presents the simple rectangular beam configuration by default', () => {
    expect(component.configuration.sectionType).toBe('RECTANGULAR');
    expect(component.configuration.submodule).toBe('BEAM_SIMPLE_RECTANGULAR');
    expect(component.configuration.supportSystem).toBe('SIMPLY_SUPPORTED');
    expect(component.configuration.materialType).toBe('REINFORCED_CONCRETE');
    expect(fixture.nativeElement.textContent).toContain('Poutre en béton armé');
  });

  it('displays the two backend-catalogued Beam submodules with an accessible active state', () => {
    const options: NodeListOf<HTMLButtonElement> = fixture.nativeElement.querySelectorAll('[role="radio"]');

    expect(options).toHaveLength(2);
    expect(options[0].textContent).toContain('Poutre rectangulaire simplement appuyée');
    expect(options[1].textContent).toContain('Poutre rectangulaire en console');
    expect(options[0].getAttribute('aria-checked')).toBe('true');
    expect(options[1].getAttribute('aria-checked')).toBe('false');
  });

  it('switches locally to the cantilever hypotheses while preserving common form values', () => {
    component.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.permanentLoadsForm.setValue({ includeSelfWeight: false, additionalPermanentLoad: 5 });

    (fixture.nativeElement.querySelectorAll('[role="radio"]')[1] as HTMLButtonElement).click();
    fixture.detectChanges();

    expect(component.configuration.submodule).toBe('BEAM_CANTILEVER_RECTANGULAR');
    expect(component.configuration.supportSystem).toBe('CANTILEVER');
    expect(component.geometryForm.getRawValue()).toEqual({ effectiveSpan: 6.5, width: 30, height: 60 });
    expect(component.permanentLoadsForm.getRawValue()).toEqual({ includeSelfWeight: false, additionalPermanentLoad: 5 });
    expect(component.isCurrentSubmoduleCalculable()).toBe(true);
    expect(component.submoduleCalculationMessage()).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Console');
    expect(fixture.nativeElement.querySelector('[data-testid="cantilever-hypotheses"]').textContent).toContain('encastrée à une extrémité');
    expect(fixture.nativeElement.textContent).toContain('Longueur efficace de la console (L)');
    expect(fixture.nativeElement.querySelectorAll('[role="radio"]')[1].getAttribute('aria-checked')).toBe('true');
  });

  it('keeps the common Beam values and sends the coherent cantilever configuration', () => {
    component.geometryForm.setValue({ effectiveSpan: 4, width: 25, height: 45 });
    component.materialsForm.setValue({ concreteClass: 'C25/30', steelGrade: 'B500B', exposureClass: 'XC1' });
    component.permanentLoadsForm.setValue({ includeSelfWeight: false, additionalPermanentLoad: 2.5 });
    component.variableLoadForm.setValue({ category: 'A', characteristicLoad: 1.25 });
    component.selectSubmodule('BEAM_CANTILEVER_RECTANGULAR');

    expect(component.payload()).toMatchObject({
      configuration: { submodule: 'BEAM_CANTILEVER_RECTANGULAR', supportSystem: 'CANTILEVER' },
      geometry: { effectiveSpan: 4000, width: 250, height: 450, unit: 'mm' },
      materials: { concreteClass: 'C25/30', steelGrade: 'B500B', exposureClasses: ['XC1'] },
      loads: {
        permanent: { includeSelfWeight: false, additionalPermanentLoad: 2.5, unit: 'kN/m' },
        variable: { category: 'A', characteristicLoad: 1.25, unit: 'kN/m' },
      },
    });
  });

  it('returns locally to the simple rectangular Beam and restores its hypotheses', () => {
    (fixture.nativeElement.querySelectorAll('[role="radio"]')[1] as HTMLButtonElement).click();
    fixture.detectChanges();
    (fixture.nativeElement.querySelectorAll('[role="radio"]')[0] as HTMLButtonElement).click();
    fixture.detectChanges();

    expect(component.configuration.submodule).toBe('BEAM_SIMPLE_RECTANGULAR');
    expect(component.configuration.supportSystem).toBe('SIMPLY_SUPPORTED');
    expect(component.isCurrentSubmoduleCalculable()).toBe(true);
    expect(component.submoduleCalculationMessage()).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Simplement appuyée');
  });

  it('displays the three geometry inputs with their explicit UI units', () => {
    expect(fixture.nativeElement.querySelector('#effective-span')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('#beam-width')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('#beam-height')).toBeTruthy();
    expect(fixture.nativeElement.textContent).toContain('Portée efficace (l_eff)');
  });

  it('displays catalog-backed material selectors and keeps valid selections', () => {
    expect(fixture.nativeElement.querySelector('#concrete-class')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('#steel-grade')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('#exposure-class')).toBeTruthy();

    component.materialsForm.setValue({ concreteClass: 'C25/30', steelGrade: 'B500B', exposureClass: 'XC1' });

    expect(component.materialsPayload()).toEqual({ concreteClass: 'C25/30', steelGrade: 'B500B', exposureClasses: ['XC1'] });
  });

  it('only presents the backend capabilities and keeps the selected exposure explicit in the payload', () => {
    const exposureOptions = [...fixture.nativeElement.querySelectorAll('#exposure-class option')].map((option: HTMLOptionElement) => option.value);
    const steelOptions = [...fixture.nativeElement.querySelectorAll('#steel-grade option')].map((option: HTMLOptionElement) => option.value);

    expect(exposureOptions).toEqual(['XC1']);
    expect(exposureOptions).not.toContain('XC4');
    expect(steelOptions).toEqual(['B500B']);
    expect(component.materialsPayload()).toEqual({ concreteClass: 'C30/37', steelGrade: 'B500B', exposureClasses: ['XC1'] });
  });

  it('defaults permanent actions to self weight included and no additional load', () => {
    expect(fixture.nativeElement.querySelector('#additional-permanent-load')).toBeTruthy();
    expect(component.permanentLoadsForm.getRawValue()).toEqual({ includeSelfWeight: true, additionalPermanentLoad: 0 });
    expect(component.permanentLoadsPayload()).toEqual({ includeSelfWeight: true, additionalPermanentLoad: 0, unit: 'kN/m' });
  });

  it('keeps permanent actions when calculation mode changes', () => {
    component.permanentLoadsForm.setValue({ includeSelfWeight: false, additionalPermanentLoad: 5 });
    component.selectCalculationMode('VERIFICATION');
    component.selectCalculationMode('DESIGN');

    expect(component.permanentLoadsForm.getRawValue()).toEqual({ includeSelfWeight: false, additionalPermanentLoad: 5 });
    expect(component.permanentLoadsPayload()).toEqual({ includeSelfWeight: false, additionalPermanentLoad: 5, unit: 'kN/m' });
  });

  it('accepts zero and positive additional permanent loads but rejects negative or non-finite values', () => {
    const control = component.permanentLoadsForm.controls.additionalPermanentLoad;

    control.setValue(0);
    expect(control.valid).toBe(true);
    control.setValue(3.5);
    expect(control.valid).toBe(true);
    control.setValue(-0.1);
    expect(control.invalid).toBe(true);
    control.setValue(Number.NaN);
    expect(control.invalid).toBe(true);
    expect(component.permanentLoadsPayload()).toBeNull();
  });

  it('displays category A and defaults Qk to zero without profile factors', () => {
    expect(fixture.nativeElement.querySelector('#characteristic-variable-load')).toBeTruthy();
    expect(fixture.nativeElement.textContent).toContain('Catégorie A');
    expect(component.variableLoadPayload()).toEqual({ category: 'A', characteristicLoad: 0, unit: 'kN/m' });
    expect(component.variableLoadPayload()).not.toHaveProperty('psi0');
    expect(component.variableLoadPayload()).not.toHaveProperty('psi1');
    expect(component.variableLoadPayload()).not.toHaveProperty('psi2');
  });

  it('accepts zero and positive Qk but rejects invalid Qk or an unsupported category', () => {
    const loadControl = component.variableLoadForm.controls.characteristicLoad;

    loadControl.setValue(0);
    expect(loadControl.valid).toBe(true);
    loadControl.setValue(3.5);
    expect(component.variableLoadPayload()).toEqual({ category: 'A', characteristicLoad: 3.5, unit: 'kN/m' });
    loadControl.setValue(-0.1);
    expect(loadControl.invalid).toBe(true);
    loadControl.setValue(Number.NaN);
    expect(loadControl.invalid).toBe(true);
    loadControl.setValue(0);
    component.variableLoadForm.controls.category.setValue('B' as 'A');
    expect(component.variableLoadForm.invalid).toBe(true);
    expect(component.variableLoadPayload()).toBeNull();
  });

  it('keeps Qk while calculation mode changes', () => {
    component.variableLoadForm.setValue({ category: 'A', characteristicLoad: 4 });
    component.selectCalculationMode('VERIFICATION');
    component.selectCalculationMode('DESIGN');

    expect(component.variableLoadPayload()).toEqual({ category: 'A', characteristicLoad: 4, unit: 'kN/m' });
  });

  it('shows existing longitudinal reinforcement only in verification mode and restores its values', () => {
    expect(fixture.nativeElement.querySelector('#tension-bar-count')).toBeNull();

    component.selectCalculationMode('VERIFICATION');
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('#tension-bar-count')).toBeTruthy();
    component.longitudinalReinforcementForm.setValue({ tensionBarCount: 4, tensionBarDiameter: 16 });
    component.selectCalculationMode('DESIGN');
    fixture.detectChanges();
    expect(fixture.nativeElement.querySelector('#tension-bar-count')).toBeNull();
    component.selectCalculationMode('VERIFICATION');

    expect(component.longitudinalReinforcementForm.getRawValue()).toEqual({ tensionBarCount: 4, tensionBarDiameter: 16 });
  });

  it('validates longitudinal reinforcement and derives the displayed area for 4 HA16', () => {
    component.selectCalculationMode('VERIFICATION');
    component.longitudinalReinforcementForm.setValue({ tensionBarCount: 4, tensionBarDiameter: 16 });
    fixture.detectChanges();

    expect(component.providedSteelArea()).toBeCloseTo(804.247719, 6);
    expect(fixture.nativeElement.textContent).toContain('804 mm²');
    expect(component.reinforcementPayload()).toEqual({
      longitudinal: { tension: { barCount: 4, barDiameter: 16, diameterUnit: 'mm' } },
    });
    expect(component.reinforcementPayload()).not.toHaveProperty('providedSteelArea');
  });

  it('rejects zero, negative and decimal bar counts and unsupported diameters', () => {
    component.selectCalculationMode('VERIFICATION');
    const count = component.longitudinalReinforcementForm.controls.tensionBarCount;
    const diameter = component.longitudinalReinforcementForm.controls.tensionBarDiameter;

    count.setValue(0);
    expect(count.invalid).toBe(true);
    count.setValue(-1);
    expect(count.invalid).toBe(true);
    count.setValue(2.5);
    expect(count.invalid).toBe(true);
    count.setValue(2);
    diameter.setValue(18);
    expect(diameter.invalid).toBe(true);
    expect(component.reinforcementPayload()).toBeNull();
  });

  it('defaults to design and switches between the two calculation modes without navigation', () => {
    expect(component.calculationMode()).toBe('DESIGN');

    component.selectCalculationMode('VERIFICATION');

    expect(component.calculationMode()).toBe('VERIFICATION');
    expect(component.configuration.calculationMode).toBe('VERIFICATION');
    component.selectCalculationMode('DESIGN');
    expect(component.calculationMode()).toBe('DESIGN');
  });

  it('exposes one pressed mode at a time to assistive technologies', () => {
    const buttons: NodeListOf<HTMLButtonElement> = fixture.nativeElement.querySelectorAll('[aria-pressed]');

    expect(buttons[0].getAttribute('aria-pressed')).toBe('true');
    expect(buttons[1].getAttribute('aria-pressed')).toBe('false');

    component.selectCalculationMode('VERIFICATION');
    fixture.detectChanges();

    expect(buttons[0].getAttribute('aria-pressed')).toBe('false');
    expect(buttons[1].getAttribute('aria-pressed')).toBe('true');
  });

  it('keeps valid geometry through calculation-mode changes and creates a millimetre payload', () => {
    component.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.selectCalculationMode('VERIFICATION');
    component.selectCalculationMode('DESIGN');

    expect(component.geometryForm.getRawValue()).toEqual({ effectiveSpan: 6.5, width: 30, height: 60 });
    expect(component.geometryPayload()).toEqual({ effectiveSpan: 6500, width: 300, height: 600, unit: 'mm' });
    expect(component.payload()).toEqual({
      configuration: { ...component.configuration, calculationMode: 'DESIGN' },
      geometry: { effectiveSpan: 6500, width: 300, height: 600, unit: 'mm' },
      materials: { concreteClass: 'C30/37', steelGrade: 'B500B', exposureClasses: ['XC1'] },
      loads: {
        permanent: { includeSelfWeight: true, additionalPermanentLoad: 0, unit: 'kN/m' },
        variable: { category: 'A', characteristicLoad: 0, unit: 'kN/m' },
      },
    });
  });

  it('omits existing reinforcement from a design payload and includes it in verification', () => {
    component.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    expect(component.payload()).not.toHaveProperty('reinforcement');

    component.selectCalculationMode('VERIFICATION');
    expect(component.payload()).toBeNull();
    component.longitudinalReinforcementForm.setValue({ tensionBarCount: 2, tensionBarDiameter: 20 });

    expect(component.payload()).toMatchObject({
      reinforcement: { longitudinal: { tension: { barCount: 2, barDiameter: 20, diameterUnit: 'mm' } } },
    });
  });

  it('exposes one global input-validity state for complete design and verification payloads', () => {
    expect(component.isInputValid()).toBe(false);
    component.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.permanentLoadsForm.setValue({ includeSelfWeight: true, additionalPermanentLoad: 5 });
    component.variableLoadForm.setValue({ category: 'A', characteristicLoad: 3.5 });

    expect(component.isInputValid()).toBe(true);
    component.selectCalculationMode('VERIFICATION');
    expect(component.isInputValid()).toBe(false);
    component.longitudinalReinforcementForm.setValue({ tensionBarCount: 4, tensionBarDiameter: 16 });
    expect(component.isInputValid()).toBe(true);
  });

  it('makes global validity fail for an invalid material or either load', () => {
    component.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.materialsForm.controls.concreteClass.setValue('UNKNOWN');
    expect(component.isInputValid()).toBe(false);
    component.materialsForm.controls.concreteClass.setValue('C30/37');
    component.permanentLoadsForm.controls.additionalPermanentLoad.setValue(-1);
    expect(component.isInputValid()).toBe(false);
    component.permanentLoadsForm.controls.additionalPermanentLoad.setValue(0);
    component.variableLoadForm.controls.characteristicLoad.setValue(-1);
    expect(component.isInputValid()).toBe(false);
  });

  it('rejects zero and negative geometry values', () => {
    component.geometryForm.setValue({ effectiveSpan: 0, width: -30, height: 0 });

    expect(component.geometryForm.controls.effectiveSpan.invalid).toBe(true);
    expect(component.geometryForm.controls.width.invalid).toBe(true);
    expect(component.geometryForm.controls.height.invalid).toBe(true);
    expect(component.geometryPayload()).toBeNull();
  });

  it('rejects an empty or non-finite geometry field', () => {
    component.geometryForm.setValue({ effectiveSpan: Number.NaN, width: null, height: 60 });

    expect(component.geometryForm.controls.effectiveSpan.invalid).toBe(true);
    expect(component.geometryForm.controls.width.invalid).toBe(true);
    expect(component.geometryPayload()).toBeNull();
  });
});
