import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';

import { AuthRegistrationService } from '../../../../features/auth/auth-registration.service';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule],
  templateUrl: './register.html',
  styleUrl: './register.css',
})
export class Register {
  private readonly registration = inject(AuthRegistrationService);
  private readonly router = inject(Router);

  readonly form = new FormGroup({
    name: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.maxLength(255)] }),
    email: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.email, Validators.maxLength(255)] }),
    password: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.minLength(8)] }),
    password_confirmation: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
  }, { validators: (group) => group.value.password === group.value.password_confirmation ? null : { passwordMismatch: true } });

  readonly isSubmitting = signal(false);
  readonly hasSubmitted = signal(false);
  readonly serverErrors = signal<Record<string, string>>({});
  readonly globalError = signal<string | null>(null);

  submit(): void {
    if (this.isSubmitting()) return;

    this.hasSubmitted.set(true);
    this.serverErrors.set({});
    this.globalError.set(null);
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.isSubmitting.set(true);
    this.registration.register(this.form.getRawValue()).pipe(finalize(() => this.isSubmitting.set(false))).subscribe({
      next: () => void this.router.navigate(['/auth/login']),
      error: (error: HttpErrorResponse) => this.displayError(error),
    });
  }

  hasFieldError(field: 'name' | 'email' | 'password' | 'password_confirmation'): boolean {
    const control = this.form.controls[field];
    return Boolean(this.serverErrors()[field])
      || (field === 'password_confirmation' && this.hasSubmitted() && this.form.hasError('passwordMismatch'))
      || (this.hasSubmitted() && control.invalid);
  }

  fieldError(field: 'name' | 'email' | 'password' | 'password_confirmation'): string | null {
    const backendMessage = this.serverErrors()[field];
    if (backendMessage) return backendMessage;

    if (field === 'password_confirmation' && this.form.hasError('passwordMismatch') && this.hasSubmitted()) {
      return 'Les mots de passe ne correspondent pas.';
    }

    const control = this.form.controls[field];
    if (!this.hasSubmitted() || !control.errors) return null;
    if (control.hasError('required')) return 'Ce champ est obligatoire.';
    if (control.hasError('email')) return 'Saisissez une adresse email valide.';
    if (control.hasError('minlength')) return 'Le mot de passe doit comporter au moins 8 caractères.';
    if (control.hasError('maxlength')) return 'Cette valeur est trop longue.';

    return 'Cette valeur est invalide.';
  }

  private displayError(error: HttpErrorResponse): void {
    const errors = error.error?.errors;
    if (error.status === 422 && errors && typeof errors === 'object') {
      this.serverErrors.set(Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, Array.isArray(messages) ? messages[0] : String(messages)])));
      return;
    }

    this.globalError.set('La création du compte est indisponible pour le moment. Veuillez réessayer.');
  }
}
