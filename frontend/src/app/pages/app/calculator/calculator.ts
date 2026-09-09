import { Component, signal } from '@angular/core';
import { ModuleSelector } from '../../../components/module-selector/module-selector';
import { BeamForm } from '../../../features/calculator/forms/beam-form/beam-form';
import { SlabForm } from '../../../features/calculator/forms/slab-form/slab-form';
import { CalculatorModule, ModuleSelectorFieldType } from '../../../../types';

@Component({
  selector: 'app-calculator',
  imports: [ModuleSelector, BeamForm, SlabForm],
  templateUrl: './calculator.html',
  styleUrl: './calculator.css',
})
export class Calculator {
  readonly modules: readonly CalculatorModule[] = [
    { id: 'Poutre', label: 'Poutres', description: 'Élément linéaire en béton armé' },
    { id: 'Dalle', label: 'Dalles', description: 'Élément surfacique en béton armé' },
  ];

  selectedModule = signal<ModuleSelectorFieldType>('Poutre');

  handleModuleChange(moduleType: ModuleSelectorFieldType): void {
    this.selectedModule.set(moduleType);
  }

  selectedModuleDescription(): string {
    return this.modules.find((module) => module.id === this.selectedModule())?.description ?? '';
  }
}
