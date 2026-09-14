import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BeamCalculationDetails } from './beam-calculation-details';
import { CalculationDetailsAccordion } from './calculation-details-accordion';

const referenceDetails: BeamCalculationDetails = {
  overallStatus: 'COMPLIANT', ulsStatus: 'COMPLIANT', slsStatus: 'COMPLIANT',
  governingVerification: { identifier: 'FLEXURE', utilization: 0.913947260705 },
  assumptions: { elementType: 'BEAM', material: 'REINFORCED_CONCRETE', crossSectionType: 'RECTANGULAR', structuralSystem: 'SIMPLY_SUPPORTED', concreteClass: 'C30/37', steelGrade: 'B500B', exposureClass: 'XC1', width: 300, height: 600, effectiveSpan: 6500, selfWeightIncluded: true },
  combinations: { ultimate: { GkTotal: 9.5, Qk: 3.5, wEd: 18.075 } },
  internalForces: { MEd: 95.45859375, VEd: 58.74375 },
  flexure: { effectiveDepth: 546, requiredArea: 413.46, fcd: 20, fyd: 434.78 },
  reinforcement: { source: 'PROPOSED', barCount: 4, barDiameter: 12, providedArea: 452.3893421169302 },
  shear: { VEd: 58.74375, VRdc: 63.86, VRdmax: 580.09, status: 'COMPLIANT' },
  serviceability: { stress: { status: 'COMPLIANT' }, crack: { crackWidth: .242950311652401, wmax: .4, status: 'COMPLIANT' }, deflection: { method: 'SIMPLIFIED_SPAN_DEPTH', actualSpanDepthRatio: 11.9, allowableSpanDepthRatio: 20, status: 'COMPLIANT' } },
  warnings: ['Méthode simplifiée de déformation.'],
};

describe('CalculationDetailsAccordion', () => {
  let fixture: ComponentFixture<CalculationDetailsAccordion>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [CalculationDetailsAccordion] }).compileComponents();
    fixture = TestBed.createComponent(CalculationDetailsAccordion);
    fixture.componentRef.setInput('details', referenceDetails);
    fixture.detectChanges();
  });

  it('renders the six ordered detail accordions and keeps only assumptions expanded initially', () => {
    const buttons = [...fixture.nativeElement.querySelectorAll('button')] as HTMLButtonElement[];

    expect(buttons.map((button) => button.textContent?.trim())).toEqual([
      '1. Hypothèses et paramètres', '2. Calcul des sollicitations', '3. Flexion', '4. Armatures', '5. Cisaillement', '6. ELS',
    ]);
    expect(buttons[0].getAttribute('aria-expanded')).toBe('true');
    expect(buttons[1].getAttribute('aria-expanded')).toBe('false');
    expect(fixture.nativeElement.textContent).toContain('Méthode simplifiée de déformation.');
  });

  it('opens an accordion through its native button and displays backend detail values without recomputing them', () => {
    const actionsButton = fixture.nativeElement.querySelectorAll('button')[1] as HTMLButtonElement;
    actionsButton.click();
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(actionsButton.getAttribute('aria-expanded')).toBe('true');
    expect(text).toContain('95,46');
    expect(text).toContain('kN·m');
    expect(text).toContain('58,74');
    expect(text).toContain('kN');
  });

  it('presents reinforcement source and ELS data, without inventing a deflection in millimetres', () => {
    const buttons = fixture.nativeElement.querySelectorAll('button') as NodeListOf<HTMLButtonElement>;
    buttons[3].click();
    buttons[5].click();
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Proposé');
    expect(text).toContain('4 HA12');
    expect(text).toContain('452,39');
    expect(text).toContain('0,24');
    expect(text).toContain('Aucune flèche en millimètres n’est calculée.');
    expect(text).not.toContain('Flèche =');
  });

  it('renders a dash for missing backend values rather than zero', () => {
    fixture.componentRef.setInput('details', { ...referenceDetails, flexure: { effectiveDepth: null, requiredArea: null } });
    fixture.detectChanges();
    fixture.nativeElement.querySelectorAll('button')[2].click();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('—');
  });

  it('presents provided reinforcement as supplied rather than proposed', () => {
    fixture.componentRef.setInput('details', {
      ...referenceDetails,
      reinforcement: { source: 'PROVIDED', barCount: 3, barDiameter: 16, providedArea: 603.1857894892403 },
    });
    fixture.detectChanges();
    fixture.nativeElement.querySelectorAll('button')[3].click();
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Fourni');
    expect(text).toContain('3 HA16');
    expect(text).not.toContain('Proposé');
  });

  it('adds formula cards to an existing accordion only when structured backend steps are present', () => {
    fixture.componentRef.setInput('details', {
      ...referenceDetails,
      calculationSteps: {
        flexure: [{ name: 'Moment réduit', formula: 'μ = MEd / (b · d² · fcd)', substitution: 'μ = 95,46×10⁶ / (300 × 546² × 20)', result: 'μ = 0,05376' }],
      },
    });
    fixture.detectChanges();
    fixture.nativeElement.querySelectorAll('button')[2].click();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Détail des calculs');
    expect(fixture.nativeElement.textContent).toContain('Moment réduit');
    expect(fixture.nativeElement.textContent).toContain('μ = MEd / (b · d² · fcd)');
  });
});
