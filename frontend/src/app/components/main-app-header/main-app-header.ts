import { Component, inject, signal } from '@angular/core';
import { LinkType } from '../../../types';
import { Router, RouterLink } from '@angular/router';
import { finalize } from 'rxjs';
import { AuthCurrentUserService } from '../../features/auth/auth-current-user.service';

@Component({
  selector: 'app-main-app-header',
  imports: [RouterLink],
  templateUrl: './main-app-header.html',
  styleUrl: './main-app-header.css',
})
export class MainAppHeader {
  private readonly router = inject(Router);
  readonly authentication = inject(AuthCurrentUserService);
  readonly isLoggingOut = signal(false);
  readonly logoutError = signal<string | null>(null);
  links = signal<LinkType[]>([
    {label: 'Dashboard', path: '/app/dashboard'},
    {label: 'Calculateur', path: '/app/calculator'}
  ]);

  logout(): void {
    if (this.isLoggingOut()) return;

    this.isLoggingOut.set(true);
    this.logoutError.set(null);
    this.authentication.logout().pipe(finalize(() => this.isLoggingOut.set(false))).subscribe({
      next: () => void this.router.navigate(['/auth/login']),
      error: () => this.logoutError.set('La déconnexion n’a pas pu être effectuée. Veuillez réessayer.'),
    });
  }
}
