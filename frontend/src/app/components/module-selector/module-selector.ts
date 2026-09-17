import { Component, input, output } from '@angular/core';
import { CalculatorModule, ModuleSelectorFieldType } from '../../../types';

@Component({
  selector: 'app-module-selector',
  templateUrl: './module-selector.html',
})
export class ModuleSelector {
  modules = input.required<readonly CalculatorModule[]>();
  selectedModule = input.required<ModuleSelectorFieldType>();
  moduleChange = output<ModuleSelectorFieldType>();

  select(moduleType: ModuleSelectorFieldType): void {
    this.moduleChange.emit(moduleType);
  }
}
