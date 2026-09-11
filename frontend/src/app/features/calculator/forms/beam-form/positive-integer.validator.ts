import { AbstractControl, ValidationErrors } from '@angular/forms';

export function positiveIntegerValidator(control: AbstractControl): ValidationErrors | null {
  return typeof control.value === 'number' && Number.isFinite(control.value) && Number.isInteger(control.value) && control.value >= 1
    ? null
    : { positiveInteger: true };
}
