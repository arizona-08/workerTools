import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ModuleSelector } from './module-selector';

describe('ModuleSelector', () => {
  let component: ModuleSelector;
  let fixture: ComponentFixture<ModuleSelector>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ModuleSelector],
    }).compileComponents();

    fixture = TestBed.createComponent(ModuleSelector);
    component = fixture.componentInstance;
    fixture.componentRef.setInput('modules', [
      { id: 'Poutre', label: 'Poutres', description: 'Poutres' },
      { id: 'Dalle', label: 'Dalles', description: 'Dalles' },
    ]);
    fixture.componentRef.setInput('selectedModule', 'Poutre');
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('emits the selected module', () => {
    const emitted: string[] = [];
    component.moduleChange.subscribe((module) => emitted.push(module));

    component.select('Dalle');

    expect(emitted).toEqual(['Dalle']);
  });
});
