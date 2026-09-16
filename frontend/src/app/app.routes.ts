import { Routes } from '@angular/router';
import { PublicLayout } from './layouts/public-layout/public-layout';
import { MainAppLayout } from './layouts/main-app-layout/main-app-layout';
import { Dashboard } from './pages/app/dashboard/dashboard';
import { Calculator } from './pages/app/calculator/calculator';

export const routes: Routes = [
  // Pages Seo
  {
    path: '',
    component: PublicLayout,
  },

  // L'authentification est prête mais volontairement indisponible dans le V1 public.
  // Les composants et services sont conservés pour sa réactivation ultérieure.
  {
    path: 'auth',
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: '/app/dashboard',
      },
      {
        path: 'login',
        redirectTo: '/app/dashboard',
      },
      {
        path: 'register',
        redirectTo: '/app/dashboard',
      },
      {
        path: '**',
        redirectTo: '/app/dashboard',
      },
    ],
  },

  // Pages Application
  {
    path: 'app',
    component: MainAppLayout,
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'dashboard',
      },
      {
        path: 'dashboard',
        component: Dashboard
      },
      {
        path: 'calculator',
        component: Calculator
      }
    ]
  },

  
];
