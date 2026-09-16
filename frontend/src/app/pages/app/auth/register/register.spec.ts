import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { vi } from 'vitest';

import { Register } from './register';

describe('Register', () => {
  let component: Register;
  let fixture: ComponentFixture<Register>;
  let http: HttpTestingController;
  const router = { navigate: vi.fn().mockResolvedValue(true) };

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Register],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        { provide: Router, useValue: router },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(Register);
    component = fixture.componentInstance;
    await fixture.whenStable();
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    http.verify();
    router.navigate.mockClear();
  });

  it('renders the registration fields and primary action', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.querySelector('input[formControlName="name"]')).toBeTruthy();
    expect(element.querySelector('input[formControlName="email"]')).toBeTruthy();
    expect(element.querySelector('input[formControlName="password"]')).toBeTruthy();
    expect(element.querySelector('input[formControlName="password_confirmation"]')).toBeTruthy();
    expect(element.textContent).toContain('Créer mon compte');
  });

  it('shows local validation feedback and does not submit an invalid form', () => {
    component.submit();
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Ce champ est obligatoire.');
    http.expectNone('/api/auth/register');
  });

  it('submits valid registration data, prevents a duplicate request and navigates after success', () => {
    component.form.setValue({
      name: 'Jeanne Dupont',
      email: 'jeanne.dupont@example.test',
      password: 'mot-de-passe-fiable',
      password_confirmation: 'mot-de-passe-fiable',
    });

    component.submit();
    component.submit();
    fixture.detectChanges();

    http.expectOne('/sanctum/csrf-cookie').flush(null);
    const request = http.expectOne('/api/auth/register');
    expect(request.request.body).toEqual(component.form.getRawValue());
    expect(component.isSubmitting()).toBe(true);
    expect((fixture.nativeElement as HTMLElement).querySelector('button')?.disabled).toBe(true);
    expect(fixture.nativeElement.textContent).toContain('Création du compte…');

    request.flush({ user: { id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' } });

    expect(component.isSubmitting()).toBe(false);
    expect(router.navigate).toHaveBeenCalledWith(['/auth/login']);
  });

  it('displays backend field errors and restores the form after a rejected registration', () => {
    component.form.setValue({
      name: 'Jeanne Dupont',
      email: 'deja.utilise@example.test',
      password: 'mot-de-passe-fiable',
      password_confirmation: 'mot-de-passe-fiable',
    });

    component.submit();
    http.expectOne('/sanctum/csrf-cookie').flush(null);
    http.expectOne('/api/auth/register').flush({
      message: 'The email has already been taken.',
      errors: { email: ['Cette adresse email est déjà utilisée.'] },
    }, { status: 422, statusText: 'Unprocessable Entity' });
    fixture.detectChanges();

    expect(component.isSubmitting()).toBe(false);
    expect(fixture.nativeElement.textContent).toContain('Cette adresse email est déjà utilisée.');
    expect(router.navigate).not.toHaveBeenCalled();
  });
});
