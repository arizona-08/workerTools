import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BeamResultSummary } from './beam-result-summary';
import { ResultSummaryCards } from './result-summary-cards';

const referenceSummary: BeamResultSummary = {
  designBendingMoment: 95.45859375,
  effectiveDepth: 546,
  requiredLongitudinalReinforcementArea: 413.46,
  longitudinalReinforcement: {
    source: 'PROPOSED',
    barCount: 4,
    barDiameter: 12,
    providedArea: 452.3893421169302,
  },
};

describe('ResultSummaryCards', () => {
  let component: ResultSummaryCards;
  let fixture: ComponentFixture<ResultSummaryCards>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ResultSummaryCards],
    }).compileComponents();

    fixture = TestBed.createComponent(ResultSummaryCards);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('summary', referenceSummary);
    fixture.detectChanges();
  });

  it('renders the four main values from BEAM-RESULT-03 with French UI formatting', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Moment de calcul');
    expect(text).toContain('95,46');
    expect(text).toContain('kN·m');
    expect(text).toContain('Hauteur utile');
    expect(text).toContain('546');
    expect(text).toContain('mm');
    expect(text).toContain('Armatures requises');
    expect(text).toContain('413,46');
    expect(text).toContain('mm²');
    expect(text).toContain('Ferraillage proposé');
    expect(text).toContain('4 HA12');
    expect(text).toContain('As = 452,39 mm²');
  });

  it('uses a supplied-reinforcement label in verification mode', () => {
    fixture.componentRef.setInput('summary', {
      ...referenceSummary,
      longitudinalReinforcement: { source: 'PROVIDED', barCount: 3, barDiameter: 16, providedArea: 603.1857894892403 },
    });
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Ferraillage fourni');
    expect(fixture.nativeElement.textContent).toContain('3 HA16');
    expect(fixture.nativeElement.textContent).not.toContain('Ferraillage proposé');
  });

  it('keeps missing values explicit rather than replacing them with zero', () => {
    fixture.componentRef.setInput('summary', {
      designBendingMoment: null,
      effectiveDepth: null,
      requiredLongitudinalReinforcementArea: null,
      longitudinalReinforcement: null,
    });
    fixture.detectChanges();

    expect(component.cards().map((card) => card.value)).toEqual(['—', '—', '—', '—']);
    expect(fixture.nativeElement.textContent).not.toContain('0 kN·m');
    expect(fixture.nativeElement.textContent).not.toContain('0 HA0');
  });
});
