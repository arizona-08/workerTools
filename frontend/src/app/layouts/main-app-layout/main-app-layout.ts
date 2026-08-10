import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { MainAppHeader } from '../../components/main-app-header/main-app-header';

@Component({
  selector: 'app-main-app-layout',
  imports: [RouterOutlet, MainAppHeader],
  templateUrl: './main-app-layout.html',
  styleUrl: './main-app-layout.css',
})
export class MainAppLayout {}
