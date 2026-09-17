export type LinkType = {
  label: string;
  path: string;
}

export type ModuleSelectorFieldType = 'Poutre' | 'Dalle';

export interface CalculatorModule {
  id: ModuleSelectorFieldType;
  label: string;
  description: string;
}
