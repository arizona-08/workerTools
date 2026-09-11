import { AbstractControl, ValidationErrors } from '@angular/forms';

export function nonNegativeFiniteNumberValidator(control: AbstractControl): ValidationErrors | null {
  return typeof control.value === 'number' && Number.isFinite(control.value) && control.value >= 0
    ? null
    : { nonNegativeFiniteNumber: true };
}
