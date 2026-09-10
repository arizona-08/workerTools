import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { MainAppHeader } from './main-app-header';

describe('MainAppHeader', () => {
  let component: MainAppHeader;
  let fixture: ComponentFixture<MainAppHeader>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MainAppHeader],
      providers: [provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(MainAppHeader);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
