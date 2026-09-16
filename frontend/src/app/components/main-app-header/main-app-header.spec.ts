import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { AuthCurrentUserService } from '../../features/auth/auth-current-user.service';
import { MainAppHeader } from './main-app-header';

describe('MainAppHeader', () => {
  let component: MainAppHeader;
  let fixture: ComponentFixture<MainAppHeader>;
  let authentication: AuthCurrentUserService;
  let http: HttpTestingController;

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
  });

  afterEach(() => http.verify());

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('does not expose authentication actions while authentication is disabled', () => {
    const element = fixture.nativeElement as HTMLElement;

    expect(element.textContent).not.toContain('Se connecter');
    expect(element.textContent).not.toContain('Créer un compte');
    expect(element.querySelector('a[href="/auth/login"]')).toBeNull();
    expect(element.querySelector('a[href="/auth/register"]')).toBeNull();
  });

  it('does not expose account controls even when a session is present', () => {
    authentication.setCurrentUser({ id: 1, name: 'Jeanne Dupont', email: 'jeanne.dupont@example.test' });
    fixture.detectChanges();

    const element = fixture.nativeElement as HTMLElement;
    expect(element.textContent).not.toContain('Jeanne Dupont');
    expect(element.textContent).not.toContain('Se déconnecter');
  });
});
