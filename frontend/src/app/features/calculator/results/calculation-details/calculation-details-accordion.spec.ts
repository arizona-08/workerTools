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

  it('makes cantilever method limitations explicit in French', () => {
    fixture.componentRef.setInput('details', {
      ...referenceDetails,
      warnings: ['CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED'],
    });
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('La section critique de cisaillement à l’encastrement n’est pas encore modélisée.');
    expect(text).not.toContain('CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED');
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
    expect(text).toContain('Ferraillage proposé');
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
    expect(text).toContain('Ferraillage fourni');
    expect(text).toContain('3 HA16');
    expect(text).not.toContain('Ferraillage proposé');
  });

  it('presents generated and geometry candidates as readable reinforcement tables rather than objects', () => {
    fixture.componentRef.setInput('details', {
      ...referenceDetails,
      reinforcement: {
        requiredArea: 413.46,
        targetArea: { targetArea: 413.46 },
        longitudinalReinforcement: { source: 'PROPOSED', barCount: 4, barDiameter: 12, providedArea: 452.3893421169302 },
        generatedCandidates: {
          candidateCount: 2,
          candidates: [
            { barCount: 3, barDiameter: 14, providedArea: 461.8141200776996, targetArea: 413.46, excessArea: 48.3541200776996, utilizationRatio: 0.895296 },
            { barCount: 4, barDiameter: 12, providedArea: 452.3893421169302, targetArea: 413.46, excessArea: 38.9293421169302, utilizationRatio: 0.913947 },
          ],
        },
        geometryCandidates: {
          acceptedCandidates: [{ candidate: { barCount: 4, barDiameter: 12, providedArea: 452.3893421169302, targetArea: 413.46, excessArea: 38.9293421169302, utilizationRatio: 0.913947 }, requiredWidth: 84, remainingWidth: 160, minimumClearSpacing: 20 }],
          rejectedCandidates: [{ candidate: { barCount: 8, barDiameter: 16, providedArea: 1608.495438637974, targetArea: 413.46, excessArea: 1195.035438637974, utilizationRatio: 0.257 }, requiredWidth: 268, remainingWidth: -24, rejectionReason: 'INSUFFICIENT_HORIZONTAL_SPACE' }],
        },
      },
    });
    fixture.detectChanges();
    fixture.nativeElement.querySelectorAll('button')[3].click();
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Propositions générées (2)');
    expect(text).toContain('3 HA14');
    expect(text).toContain('4 HA12');
    expect(text).toContain('Largeur disponible insuffisante');
    expect(text).not.toContain('[object Object]');
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
