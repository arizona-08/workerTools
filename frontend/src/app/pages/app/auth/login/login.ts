import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { finalize } from 'rxjs';

import { AuthLoginService } from '../../../../features/auth/auth-login.service';
import { AuthCurrentUserService } from '../../../../features/auth/auth-current-user.service';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private readonly authentication = inject(AuthLoginService);
  private readonly currentUser = inject(AuthCurrentUserService);
  private readonly router = inject(Router);

  readonly form = new FormGroup({
    email: new FormControl('', { nonNullable: true, validators: [Validators.required, Validators.email] }),
    password: new FormControl('', { nonNullable: true, validators: [Validators.required] }),
  });
  readonly isSubmitting = signal(false);
  readonly hasSubmitted = signal(false);
  readonly error = signal<string | null>(null);

  submit(): void {
    if (this.isSubmitting()) return;

    this.hasSubmitted.set(true);
    this.error.set(null);
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.isSubmitting.set(true);
    this.authentication.login(this.form.getRawValue()).pipe(finalize(() => this.isSubmitting.set(false))).subscribe({
      next: ({ user }) => {
        this.currentUser.setCurrentUser(user);
        void this.router.navigate(['/app/calculator']);
      },
      error: (httpError: HttpErrorResponse) => this.error.set(this.errorMessage(httpError)),
    });
  }

  fieldError(field: 'email' | 'password'): string | null {
    const control = this.form.controls[field];
    if (!this.hasSubmitted() || !control.errors) return null;
    if (control.hasError('required')) return 'Ce champ est obligatoire.';
    if (control.hasError('email')) return 'Saisissez une adresse email valide.';

    return 'Cette valeur est invalide.';
  }

  private errorMessage(error: HttpErrorResponse): string {
    if (error.status === 422) return 'Email ou mot de passe incorrect.';
    if (error.status === 0) return 'La connexion au serveur a échoué. Vérifiez votre réseau puis réessayez.';

    return 'La connexion est indisponible pour le moment. Veuillez réessayer.';
  }
}
