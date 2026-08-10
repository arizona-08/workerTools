import { Component, output, signal } from '@angular/core';
import { ModuleSelectorFieldType } from '../../../types';
import { LucideChevronDown } from '@lucide/angular';

@Component({
  selector: 'app-module-selector',
  imports: [LucideChevronDown],
  templateUrl: './module-selector.html',
  styleUrl: './module-selector.css',
})
export class ModuleSelector {
  allModules: ModuleSelectorFieldType[] = ['Poutre', 'Dalle'];
  selectedModule = signal<ModuleSelectorFieldType>('Poutre');
  isModuleListOpen = signal<boolean>(false);

  onSelectModuleForm = output<ModuleSelectorFieldType>();

  toggleModuleList(){
    this.isModuleListOpen.update(prev => !prev)
  }

  handleChangeModule(moduleType: ModuleSelectorFieldType){
    this.selectedModule.set(moduleType);
    this.isModuleListOpen.set(false);
    this.onSelectModuleForm.emit(moduleType)
  }
}
