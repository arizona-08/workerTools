import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { By } from '@angular/platform-browser';

import { Calculator } from './calculator';
import { BeamForm } from '../../../features/calculator/forms/beam-form/beam-form';
import { SlabForm } from '../../../features/calculator/forms/slab-form/slab-form';

describe('Calculator', () => {
  let component: Calculator;
  let fixture: ComponentFixture<Calculator>;
  let http: HttpTestingController;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Calculator],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(Calculator);
    component = fixture.componentInstance;
    http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [12, 16], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });
    fixture.detectChanges();
  });

  afterEach(() => http.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('selects one module at a time', () => {
    component.handleModuleChange('Dalle');
    fixture.detectChanges();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [12, 16], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });

    expect(component.selectedModule()).toBe('Dalle');
    expect(fixture.debugElement.query(By.directive(SlabForm))).toBeTruthy();
    expect(fixture.debugElement.query(By.directive(BeamForm))).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Dalle pleine');
    expect(fixture.nativeElement.querySelector('button[type="button"][disabled]')).toBeNull();
    http.expectNone('/api/beam/calculations');
  });

  it('clears a beam result when switching to Dalle and restores the beam form without calculating', () => {
    component.calculationResult.set({
      summary: { utilization: 0.9, status: 'COMPLIANT', governingVerificationType: 'FLEXURE', designBendingMoment: 1, effectiveDepth: 1, requiredLongitudinalReinforcementArea: 1, longitudinalReinforcement: null },
      verifications: {}, details: { overallStatus: 'COMPLIANT', ulsStatus: 'COMPLIANT', slsStatus: 'COMPLIANT', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] },
    });

    component.handleModuleChange('Dalle');
    fixture.detectChanges();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [12, 16], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });
    expect(component.calculationResult()).toBeNull();
    expect(fixture.debugElement.query(By.directive(SlabForm))).toBeTruthy();
    http.expectNone('/api/beam/calculations');

    component.handleModuleChange('Poutre');
    fixture.detectChanges();
    expect(fixture.debugElement.query(By.directive(BeamForm))).toBeTruthy();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [12, 16], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });
    http.expectNone('/api/beam/calculations');
  });

  it('submits a valid beam input, exposes loading state, then renders the backend result', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    form.permanentLoadsForm.setValue({ includeSelfWeight: true, additionalPermanentLoad: 5 });
    form.variableLoadForm.setValue({ category: 'A', characteristicLoad: 3.5 });

    component.calculateBeam();
    expect(component.isCalculating()).toBe(true);
    const request = http.expectOne('/api/beam/calculations');
    expect(request.request.method).toBe('POST');
    expect(request.request.body.geometry).toEqual({ effectiveSpan: 6500, width: 300, height: 600, unit: 'mm' });
    request.flush({
      summary: { utilization: 0.913947260705, status: 'COMPLIANT', governingVerificationType: 'FLEXURE', designBendingMoment: 95.45859375, effectiveDepth: 546, requiredLongitudinalReinforcementArea: 413.46, longitudinalReinforcement: { source: 'PROPOSED', barCount: 4, barDiameter: 12, providedArea: 452.3893421169302 } },
      verifications: {},
      details: { overallStatus: 'COMPLIANT', ulsStatus: 'COMPLIANT', slsStatus: 'COMPLIANT', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] },
    });
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Conformité du calcul');
    expect(fixture.nativeElement.textContent).toContain('Conforme');
  });

  it('applies the same loading and double-submission protection to a slab calculation', () => {
    component.handleModuleChange('Dalle');
    fixture.detectChanges();
    http.expectOne('/api/beam/material-catalog').flush({
      concreteClasses: ['C20/25', 'C25/30', 'C30/37'], steelGrades: ['B500B'], reinforcementBarDiameters: [12, 16], exposureClasses: [{ code: 'XC1', label: 'Sec ou humide en permanence' }],
    });
    const form = fixture.debugElement.query(By.directive(SlabForm)).componentInstance as SlabForm;
    form.geometryForm.setValue({ effectiveSpan: 5, thickness: 20 });
    form.surfaceLoadsForm.setValue({ finishes: 1.5, partitions: 0, otherPermanent: 0, imposedLoad: 2 });

    component.calculateSlab();
    component.calculateSlab();
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(true);
    expect(fixture.nativeElement.textContent).toContain('Calcul en cours…');
    const requests = http.match('/api/slab/calculations');
    expect(requests).toHaveLength(1);
    requests[0].flush({
      status: 'COMPLIANT',
      summary: { status: 'COMPLIANT', utilization: 0.75, governingVerificationType: 'FLEXURE', designBendingMoment: 10.1234, effectiveDepth: 160, requiredMainReinforcementArea: 200.5, minimumMainReinforcementArea: 150, mainReinforcement: null, secondaryReinforcement: null },
      verifications: [], details: {},
    });
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Conformité de la dalle');
    expect(fixture.nativeElement.textContent).toContain('10,12 kN·m');
  });

  it('shows an actionable backend error and restores the submission button', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });

    component.calculateBeam();
    http.expectOne('/api/beam/calculations').flush({ message: 'Configuration non prise en charge.' }, { status: 422, statusText: 'Unprocessable Entity' });
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Configuration non prise en charge.');
  });

  it('prevents concurrent submissions and removes an obsolete result while loading', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.calculationResult.set({
      summary: { utilization: 0.9, status: 'COMPLIANT', governingVerificationType: 'FLEXURE', designBendingMoment: 1, effectiveDepth: 1, requiredLongitudinalReinforcementArea: 1, longitudinalReinforcement: null },
      verifications: {}, details: { overallStatus: 'COMPLIANT', ulsStatus: 'COMPLIANT', slsStatus: 'COMPLIANT', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] },
    });

    component.calculateBeam();
    component.calculateBeam();
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(true);
    expect(component.calculationResult()).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Calcul en cours…');
    expect(fixture.nativeElement.querySelector('button[type="button"]').disabled).toBe(true);
    const requests = http.match('/api/beam/calculations');
    expect(requests.length).toBe(1);
    requests[0].flush({ summary: { utilization: null, status: 'NOT_CHECKED', governingVerificationType: null, designBendingMoment: null, effectiveDepth: null, requiredLongitudinalReinforcementArea: null, longitudinalReinforcement: null }, verifications: {}, details: { overallStatus: 'NOT_CHECKED', ulsStatus: 'NOT_CHECKED', slsStatus: 'NOT_CHECKED', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] } });
  });

  it('uses a safe message for server and network failures and restores the CTA', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });

    component.calculateBeam();
    http.expectOne('/api/beam/calculations').flush({ message: 'Trace technique à ne pas afficher' }, { status: 500, statusText: 'Server Error' });
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Le calcul n’a pas pu être exécuté. Réessayez dans quelques instants.');
    expect(fixture.nativeElement.textContent).not.toContain('Trace technique à ne pas afficher');
  });

  it('keeps validation messages for 400 errors and uses the generic fallback on network errors', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });

    component.calculateBeam();
    http.expectOne('/api/beam/calculations').flush({ message: 'La largeur fournie est invalide.' }, { status: 400, statusText: 'Bad Request' });
    fixture.detectChanges();
    expect(fixture.nativeElement.textContent).toContain('La largeur fournie est invalide.');

    component.calculateBeam();
    http.expectOne('/api/beam/calculations').error(new ProgressEvent('error'));
    fixture.detectChanges();

    expect(component.isCalculating()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Le calcul n’a pas pu être exécuté. Réessayez dans quelques instants.');
  });

  it('sends a changed concrete selection and clears an obsolete result before the next calculation', () => {
    const form = fixture.debugElement.query(By.directive(BeamForm)).componentInstance as BeamForm;
    form.geometryForm.setValue({ effectiveSpan: 6.5, width: 30, height: 60 });
    component.calculationResult.set({
      summary: { utilization: 0.9, status: 'COMPLIANT', governingVerificationType: 'FLEXURE', designBendingMoment: 1, effectiveDepth: 1, requiredLongitudinalReinforcementArea: 1, longitudinalReinforcement: null },
      verifications: {}, details: { overallStatus: 'COMPLIANT', ulsStatus: 'COMPLIANT', slsStatus: 'COMPLIANT', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] },
    });
    form.materialsForm.controls.concreteClass.setValue('C25/30');
    fixture.detectChanges();

    expect(component.calculationResult()).toBeNull();
    component.calculateBeam();
    const request = http.expectOne('/api/beam/calculations');
    expect(request.request.body.materials).toEqual({ concreteClass: 'C25/30', steelGrade: 'B500B', exposureClasses: ['XC1'] });
    request.flush({
      summary: { utilization: null, status: 'NOT_CHECKED', governingVerificationType: null, designBendingMoment: null, effectiveDepth: null, requiredLongitudinalReinforcementArea: null, longitudinalReinforcement: null },
      verifications: {}, details: { overallStatus: 'NOT_CHECKED', ulsStatus: 'NOT_CHECKED', slsStatus: 'NOT_CHECKED', governingVerification: null, assumptions: {}, combinations: {}, internalForces: {}, flexure: {}, reinforcement: {}, shear: {}, serviceability: {}, warnings: [] },
    });
  });
});
