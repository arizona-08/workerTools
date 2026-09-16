import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router, provideRouter } from '@angular/router';
import { vi } from 'vitest';

import { AuthCurrentUserService } from '../../features/auth/auth-current-user.service';
import { MainAppHeader } from './main-app-header';

describe('MainAppHeader', () => {
  let component: MainAppHeader;
  let fixture: ComponentFixture<MainAppHeader>;
  let authentication: AuthCurrentUserService;
  let http: HttpTestingController;
  let router: Router;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MainAppHeader],
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();

    fixture = TestBed.createComponent(MainAppHeader);
    component = fixture.componentInstance;
    await fixture.whenStable();
    authentication = TestBed.inject(AuthCurrentUserService);
    http = TestBed.inject(HttpTestingController);
    router = TestBed.inject(Router);
  });

  afterEach(() => http.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('offers optional authentication actions to a guest without blocking navigation', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).toContain('Se connecter');
    expect(element.textContent).toContain('Créer un compte');
    expect(element.querySelector('a[href="/auth/login"]')).toBeTruthy();
    expect(element.querySelector('a[href="/auth/register"]')).toBeTruthy();
  });

  it('shows the authenticated user and clears the session after logout', () => {
    const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);
    authentication.setCurrentUser({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });
    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;
    expect(element.textContent).toContain('Jeanne Dupont');
    expect(element.textContent).toContain('jeanne.dupont@example.test');

    const button = Array.from(element.querySelectorAll('button')).find((candidate) => candidate.textContent?.includes('Se déconnecter')) as HTMLButtonElement;
    expect(button).toBeTruthy();
    button.click();

    http.expectOne('/sanctum/csrf-cookie').flush(null);
    http.expectOne('/api/auth/logout').flush(null, { status: 204, statusText: 'No Content' });

    expect(authentication.currentUser()).toBeNull();
    expect(navigate).toHaveBeenCalledWith(['/auth/login']);
  });
});
