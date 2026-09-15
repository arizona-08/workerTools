import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ResultComplianceIndicator, VerificationStatus, VerificationType } from './result-compliance-indicator';

describe('ResultComplianceIndicator', () => {
  let component: ResultComplianceIndicator;
  let fixture: ComponentFixture<ResultComplianceIndicator>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ResultComplianceIndicator],
    }).compileComponents();

    fixture = TestBed.createComponent(ResultComplianceIndicator);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('utilization', 0.913947260705);
    fixture.componentRef.setInput('status', 'COMPLIANT');
    fixture.componentRef.setInput('governingVerificationType', 'FLEXURE');
    fixture.detectChanges();
  });

  it('displays the backend utilization as a presentation percentage', () => {
    expect(component.displayedPercentage()).toBe(91);
    expect(component.visualProgress()).toBeCloseTo(0.913947260705);
    expect(component.governingVerificationLabel()).toBe('Flexion');
  });

  it('uses the backend status rather than deriving it from utilization', () => {
    fixture.componentRef.setInput('utilization', 0.25);
    fixture.componentRef.setInput('status', 'NOT_COMPLIANT');
    fixture.detectChanges();

    expect(component.presentation().label).toBe('Non conforme');
    expect(fixture.nativeElement.textContent).toContain('Non conforme');
  });

  it('renders an explicit unavailable state when the utilization is absent', () => {
    fixture.componentRef.setInput('utilization', null);
    fixture.componentRef.setInput('status', 'NOT_CHECKED');
    fixture.componentRef.setInput('governingVerificationType', null);
    fixture.detectChanges();

    expect(component.displayedPercentage()).toBeNull();
    expect(fixture.nativeElement.textContent).toContain('Vérification incomplète');
    expect(fixture.nativeElement.textContent).not.toContain('Vérification gouvernante');
    expect(fixture.nativeElement.querySelector('section').getAttribute('aria-label')).toBe('Statut vérification incomplète, aucune vérification gouvernante disponible.');
  });

  it('does not expose NaN when an invalid utilization reaches the presentation layer', () => {
    fixture.componentRef.setInput('utilization', Number.NaN);
    fixture.componentRef.setInput('status', 'NOT_CHECKED');
    fixture.detectChanges();

    expect(component.displayedPercentage()).toBeNull();
    expect(fixture.nativeElement.textContent).not.toContain('NaN');
  });

  it('keeps the actual displayed percentage while limiting an exceeded donut to 100 percent', () => {
    fixture.componentRef.setInput('utilization', 1.12);
    fixture.componentRef.setInput('status', 'NOT_COMPLIANT');
    fixture.componentRef.setInput('governingVerificationType', 'CRACK');
    fixture.detectChanges();

    expect(component.displayedPercentage()).toBe(112);
    expect(component.visualProgress()).toBe(1);
    expect(component.governingVerificationLabel()).toBe('Fissuration');
    expect(fixture.nativeElement.textContent).toContain('112');
    expect(fixture.nativeElement.textContent).toContain('Non conforme');
  });

  it.each([
    ['COMPLIANT', 'Conforme'],
    ['NOT_COMPLIANT', 'Non conforme'],
    ['NOT_CHECKED', 'Vérification incomplète'],
    ['NOT_APPLICABLE', 'Non applicable'],
    ['CALCULATION_METHOD_NOT_SUPPORTED', 'Méthode non prise en charge'],
  ] as const)('maps the backend status %s to its explicit accessible label', (status, label) => {
    fixture.componentRef.setInput('status', status as VerificationStatus);
    fixture.detectChanges();

    expect(component.presentation().label).toBe(label);
    expect(fixture.nativeElement.textContent).toContain(label);
  });

  it.each([
    ['FLEXURE', 'Flexion'],
    ['SHEAR', 'Cisaillement'],
    ['STRESS', 'Contraintes ELS'],
    ['CRACK', 'Fissuration'],
    ['DEFLECTION', 'Déformation'],
  ] as const)('maps governing verification %s to the frontend label %s', (type, label) => {
    fixture.componentRef.setInput('governingVerificationType', type as VerificationType);
    fixture.detectChanges();

    expect(component.governingVerificationLabel()).toBe(label);
  });
});
