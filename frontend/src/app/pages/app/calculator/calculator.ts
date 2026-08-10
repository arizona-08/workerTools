import { Component, signal } from '@angular/core';
import { ModuleSelector } from "../../../components/module-selector/module-selector";
import { ModuleSelectorFieldType } from '../../../../types';

@Component({
  selector: 'app-calculator',
  imports: [ModuleSelector],
  templateUrl: './calculator.html',
  styleUrl: './calculator.css',
})
export class Calculator {
  selectedModule = signal<ModuleSelectorFieldType>('Poutre');

  handleModuleChange(moduleType: ModuleSelectorFieldType){
    this.selectedModule.set(moduleType);
  }
}
