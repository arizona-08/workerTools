import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';

import { SlabForm } from './slab-form';

describe('SlabForm', () => {
  let component: SlabForm;
  let fixture: ComponentFixture<SlabForm>;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [SlabForm], providers: [provideHttpClient(), provideHttpClientTesting()] }).compileComponents();
    fixture = TestBed.createComponent(SlabForm);
    component = fixture.componentInstance;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [8, 10], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('presents the fixed V1 configuration, geometry and surface-load inputs', () => {
    expect(component.configuration).toEqual({
      elementType: 'SLAB',
      slabType: 'SOLID',
      spanningSystem: 'ONE_WAY',
      structuralSystem: 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES',
      loadModel: 'VERTICAL_UNIFORMLY_DISTRIBUTED',
      materialType: 'REINFORCED_CONCRETE',
      designCodeProfile: 'NF_EN_1992_1_1_2005_FR',
      designSituation: 'PERSISTENT_TRANSIENT',
    });
    expect(fixture.nativeElement.textContent).toContain('Dalle pleine');
    expect(fixture.nativeElement.textContent).toContain('Unidirectionnelle');
    expect(fixture.nativeElement.textContent).toContain('Béton armé');
    expect(fixture.nativeElement.textContent).toContain('Une travée');
    expect(fixture.nativeElement.textContent).toContain('Bande simplement appuyée sur deux côtés opposés');
    expect(fixture.nativeElement.textContent).toContain('Verticales uniformément réparties');
    expect(fixture.nativeElement.querySelector('#slab-effective-span')).toBeTruthy();
    expect(fixture.nativeElement.querySelector('#slab-thickness')).toBeTruthy();
    expect(fixture.nativeElement.querySelectorAll('select, input')).toHaveLength(9);
    expect(fixture.nativeElement.textContent).toContain('Bande de calcul automatique : 1 m');
    expect(fixture.nativeElement.textContent).toContain('Charges surfaciques');
    expect(fixture.nativeElement.textContent).toContain('Calculé automatiquement à partir de l’épaisseur de la dalle.');
    expect(fixture.nativeElement.querySelector('#slab-self-weight')).toBeNull();
    expect(fixture.nativeElement.querySelector('[formControlName="calculationStripWidth"]')).toBeNull();
  });

  it('uses the shared material capabilities without exposing unsupported options', () => {
    expect(fixture.nativeElement.textContent).toContain('Matériaux et durabilité');
    expect([...fixture.nativeElement.querySelectorAll('#slab-concrete-class option')].map((option: HTMLOptionElement) => option.value)).toEqual(['C20/25', 'C25/30', 'C30/37']);
    expect([...fixture.nativeElement.querySelectorAll('#slab-steel-grade option')].map((option: HTMLOptionElement) => option.value)).toEqual(['B500B']);
    expect([...fixture.nativeElement.querySelectorAll('#slab-exposure-class option')].map((option: HTMLOptionElement) => option.value)).toEqual(['XC1']);
    expect(fixture.nativeElement.textContent).not.toContain('C99/99');
  });

  it('keeps material selections as editable form values without starting a calculation', () => {
    component.materialsForm.controls.concreteClass.setValue('C25/30');
    component.materialsForm.controls.exposureClass.setValue('XC1');
    component.materialsForm.markAsDirty();

    expect(component.materialsForm.dirty).toBe(true);
    expect(component.materialsForm.valid).toBe(true);
  });

  it('notifies the calculator when a slab input changes so an obsolete result can be removed', () => {
    let changes = 0;
    component.formChanged.subscribe(() => changes++);

    component.geometryForm.controls.thickness.setValue(20);

    expect(changes).toBe(1);
  });

  it('validates geometry and converts UI metres and centimetres to internal millimetres once', () => {
    component.geometryForm.setValue({ effectiveSpan: 5.35, thickness: 20 });

    expect(component.geometryPayload()).toEqual({ effectiveSpan: 5350, thickness: 200, unit: 'mm' });
  });

  it('rejects missing, zero and negative geometry values without exposing a strip-width input', () => {
    expect(component.geometryPayload()).toBeNull();
    component.geometryForm.controls.effectiveSpan.setValue(0);
    component.geometryForm.controls.thickness.setValue(-20);
    component.geometryForm.markAllAsTouched();
    fixture.detectChanges();

    expect(component.geometryForm.controls.effectiveSpan.invalid).toBe(true);
    expect(component.geometryForm.controls.thickness.invalid).toBe(true);
    expect(component.geometryPayload()).toBeNull();
    expect(fixture.nativeElement.querySelector('[name="stripWidth"], #strip-width, #slab-width')).toBeNull();
  });

  it('keeps the surface loads separate, in kN/m², without calculating self weight', () => {
    component.surfaceLoadsForm.setValue({ finishes: 1.5, partitions: 1, otherPermanent: 0.5, imposedLoad: 2 });

    expect(component.surfaceLoadsPayload()).toEqual({ finishes: 1.5, partitions: 1, otherPermanent: 0.5, imposedLoad: 2, unit: 'kN/m²' });
    expect(fixture.nativeElement.textContent).toContain('Revêtement');
    expect(fixture.nativeElement.textContent).toContain('Cloisons');
    expect(fixture.nativeElement.textContent).toContain('Autres permanentes');
    expect(fixture.nativeElement.textContent).toContain('Charge d’exploitation (Qk)');
    expect(fixture.nativeElement.querySelectorAll('#slab-finishes-unit, #slab-partitions-unit, #slab-other-permanent-unit, #slab-imposed-load-unit')).toHaveLength(4);
  });

  it('accepts zero but rejects negative and absent surface loads', () => {
    component.surfaceLoadsForm.setValue({ finishes: 0, partitions: 0, otherPermanent: 0, imposedLoad: 0 });
    expect(component.surfaceLoadsForm.valid).toBe(true);

    component.surfaceLoadsForm.controls.finishes.setValue(-1);
    expect(component.surfaceLoadsForm.controls.finishes.invalid).toBe(true);
    expect(component.surfaceLoadsPayload()).toBeNull();

    component.surfaceLoadsForm.controls.finishes.setValue(0);
    component.surfaceLoadsForm.controls.imposedLoad.setValue(null);
    expect(component.surfaceLoadsForm.controls.imposedLoad.invalid).toBe(true);

    component.surfaceLoadsForm.controls.imposedLoad.setValue(Number.NaN);
    expect(component.surfaceLoadsForm.controls.imposedLoad.invalid).toBe(true);
  });
});
