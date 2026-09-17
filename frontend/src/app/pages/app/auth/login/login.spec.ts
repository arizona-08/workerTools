import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { vi } from 'vitest';

import { Login } from './login';

describe('Login', () => {
  let component: Login;
  let fixture: ComponentFixture<Login>;
  let http: HttpTestingController;
  const router = { navigate: vi.fn().mockResolvedValue(true) };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Login],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: Router, useValue: router },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(Login);
    component = fixture.componentInstance;
    await fixture.whenStable();
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
    router.navigate.mockClear();
  });

  it('renders email, password and the connection action', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('input[formControlName="email"]')).toBeTruthy();
    expect(element.querySelector('input[formControlName="password"]')).toBeTruthy();
    expect(element.textContent).toContain('Se connecter');
  });

  it('shows local validation feedback without making an HTTP call for an invalid form', () => {
    component.submit();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Ce champ est obligatoire.');
    http.expectNone('/sanctum/csrf-cookie');
    http.expectNone('/api/auth/login');
  });

  it('shows loading, prevents a second submission and navigates to the calculator after login', () => {
    component.form.setValue({ email: 'jeanne.dupont@example.test', password: 'mot-de-passe-fiable' });

    component.submit();
    component.submit();
    fixture.detectChanges();

    const csrfRequest = http.expectOne('/sanctum/csrf-cookie');
    expect(component.isSubmitting()).toBe(true);
    expect((fixture.nativeElement as HTMLElement).querySelector('button')?.disabled).toBe(true);
    expect(fixture.nativeElement.textContent).toContain('Connexion en cours…');
    csrfRequest.flush(null);

    const loginRequest = http.expectOne('/api/auth/login');
    expect(loginRequest.request.body).toEqual(component.form.getRawValue());
    loginRequest.flush({ user: { id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' } });

    expect(component.isSubmitting()).toBe(false);
    expect(router.navigate).toHaveBeenCalledWith(['/app/calculator']);
  });

  it('displays a generic message for incorrect credentials', () => {
    component.form.setValue({ email: 'jeanne.dupont@example.test', password: 'mauvais-mot-de-passe' });

    component.submit();
    http.expectOne('/sanctum/csrf-cookie').flush(null);
    http.expectOne('/api/auth/login').flush({
      message: 'The given data was invalid.',
      errors: { email: ['Email ou mot de passe incorrect.'] },
    }, { status: 422, statusText: 'Unprocessable Entity' });
    fixture.detectChanges();

    expect(component.isSubmitting()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Email ou mot de passe incorrect.');
    expect(router.navigate).not.toHaveBeenCalled();
  });
});
