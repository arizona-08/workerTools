import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Calculator } from './calculator';

describe('Calculator', () => {
  let component: Calculator;
  let fixture: ComponentFixture<Calculator>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Calculator],
    }).compileComponents();

    fixture = TestBed.createComponent(Calculator);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('selects one module at a time', () => {
    component.handleModuleChange('Dalle');

    expect(component.selectedModule()).toBe('Dalle');
  });
});
