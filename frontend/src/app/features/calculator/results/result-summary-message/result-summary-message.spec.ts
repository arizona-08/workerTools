import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ResultSummaryMessage } from './result-summary-message';

describe('ResultSummaryMessage', () => {
  let component: ResultSummaryMessage;
  let fixture: ComponentFixture<ResultSummaryMessage>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ResultSummaryMessage],
    }).compileComponents();

    fixture = TestBed.createComponent(ResultSummaryMessage);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('status', 'COMPLIANT');
    fixture.componentRef.setInput('utilization', 0.913947260705);
    fixture.componentRef.setInput('governingVerificationType', 'FLEXURE');
    fixture.detectChanges();
  });

  it('renders the bounded compliant message and its informative governing verification', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('La section satisfait les vérifications réalisées dans le périmètre actuel selon l’Eurocode 2.');
    expect(text).toContain('Vérification la plus sollicitée : Flexion — 91 %.');
  });

  it('uses NOT_COMPLIANT as the source of a negative message, without making a recommendation', () => {
    fixture.componentRef.setInput('status', 'NOT_COMPLIANT');
    fixture.componentRef.setInput('utilization', 1.12);
    fixture.componentRef.setInput('governingVerificationType', 'CRACK');
    fixture.detectChanges();

    expect(component.presentation().message).toBe('La section ne satisfait pas toutes les vérifications réalisées dans le périmètre actuel.');
    expect(component.secondaryMessage()).toBe('Vérification la plus sollicitée : Fissuration — 112 %.');
  });

  it('does not conclude compliance from an under-limit utilization when status is NOT_CHECKED', () => {
    fixture.componentRef.setInput('status', 'NOT_CHECKED');
    fixture.componentRef.setInput('utilization', 0.75);
    fixture.componentRef.setInput('governingVerificationType', 'FLEXURE');
    fixture.detectChanges();

    expect(component.presentation().message).toContain('Aucune conclusion complète de conformité ne peut être établie.');
    expect(fixture.nativeElement.textContent).not.toContain('La section satisfait les vérifications');
    expect(component.secondaryMessage()).toBe('Vérification la plus sollicitée : Flexion — 75 %.');
  });

  it('does not invent a percentage or governing verification when neither is available', () => {
    fixture.componentRef.setInput('status', 'NOT_CHECKED');
    fixture.componentRef.setInput('utilization', null);
    fixture.componentRef.setInput('governingVerificationType', null);
    fixture.detectChanges();

    expect(component.displayedPercentage()).toBeNull();
    expect(component.secondaryMessage()).toBeNull();
    expect(fixture.nativeElement.textContent).not.toContain('Vérification la plus sollicitée');
  });

  it.each([
    ['NOT_APPLICABLE', 'Cette vérification n’est pas applicable à la configuration étudiée.'],
    ['CALCULATION_METHOD_NOT_SUPPORTED', 'Une vérification requise n’est pas prise en charge par la méthode actuellement implémentée. Le calcul ne permet pas de conclure à une conformité globale.'],
  ] as const)('supports the %s backend state', (status, message) => {
    fixture.componentRef.setInput('status', status);
    fixture.detectChanges();

    expect(component.presentation().message).toBe(message);
  });

  it.each([
    ['FLEXURE', 'Flexion'],
    ['SHEAR', 'Cisaillement'],
    ['STRESS', 'Contraintes ELS'],
    ['CRACK', 'Fissuration'],
    ['DEFLECTION', 'Déformation'],
  ] as const)('uses the same readable governing label for %s', (type, label) => {
    fixture.componentRef.setInput('governingVerificationType', type);
    fixture.detectChanges();

    expect(component.governingVerificationLabel()).toBe(label);
  });
});
