import { ComponentFixture, TestBed } from '@angular/core/testing';
import { BeamForm } from './beam-form';

describe('BeamForm', () => {
  let component: BeamForm;
  let fixture: ComponentFixture<BeamForm>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BeamForm],
    }).compileComponents();

    fixture = TestBed.createComponent(BeamForm);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('presents the fixed MVP beam configuration without editable alternatives', () => {
    expect(component.configuration.sectionType).toBe('RECTANGULAR');
    expect(component.configuration.supportSystem).toBe('SIMPLY_SUPPORTED');
    expect(component.configuration.materialType).toBe('REINFORCED_CONCRETE');
    expect(fixture.nativeElement.textContent).toContain('Poutre en béton armé');
  });
});
