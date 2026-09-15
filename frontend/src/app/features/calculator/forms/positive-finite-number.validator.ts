import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

/** Valide une longueur ou une autre grandeur strictement positive et finie. */
export const positiveFiniteNumberValidator: ValidatorFn = (control: AbstractControl): ValidationErrors | null => {
  const value = control.value;

  return typeof value === 'number' && Number.isFinite(value) && value > 0 ? null : { positiveFiniteNumber: true };
};
