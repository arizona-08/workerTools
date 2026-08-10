import { Component, signal } from '@angular/core';
import { LinkType } from '../../../types';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-main-app-header',
  imports: [RouterLink],
  templateUrl: './main-app-header.html',
  styleUrl: './main-app-header.css',
})
export class MainAppHeader {
  links = signal<LinkType[]>([
    {label: 'Dashboard', path: '/app/dashboard'},
    {label: 'Calculateur', path: '/app/calculator'}
  ])
}
