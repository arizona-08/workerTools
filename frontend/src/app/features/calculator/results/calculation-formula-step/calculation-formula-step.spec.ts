import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CalculationFormulaStepComponent } from './calculation-formula-step';

describe('CalculationFormulaStepComponent', () => {
  let fixture: ComponentFixture<CalculationFormulaStepComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [CalculationFormulaStepComponent] }).compileComponents();
    fixture = TestBed.createComponent(CalculationFormulaStepComponent);
  });

  it('renders backend-provided formula content without evaluating it', () => {
    fixture.componentRef.setInput('step', {
      name: 'Moment réduit',
      formula: 'μ = MEd / (b · d² · fcd)',
      substitution: 'μ = 95,46×10⁶ / (300 × 546² × 20)',
      result: 'μ = 0,05376',
    });
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Moment réduit');
    expect(text).toContain('μ = MEd / (b · d² · fcd)');
    expect(text).toContain('μ = 95,46×10⁶ / (300 × 546² × 20)');
    expect(text).toContain('μ = 0,05376');
  });

  it('hides missing formula fields and still shows the result', () => {
    fixture.componentRef.setInput('step', { name: 'Étape', formula: null, substitution: null, result: '42' });
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('42');
    expect(fixture.nativeElement.textContent).not.toContain('null');
    expect(fixture.nativeElement.textContent).not.toContain('Formule');
  });
});
