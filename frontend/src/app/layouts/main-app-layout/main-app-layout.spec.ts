import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';

import { MainAppLayout } from './main-app-layout';

describe('MainAppLayout', () => {
  let component: MainAppLayout;
  let fixture: ComponentFixture<MainAppLayout>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MainAppLayout],
      providers: [provideRouter([])],
    }).compileComponents();

    fixture = TestBed.createComponent(MainAppLayout);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
