import { Component } from '@angular/core';
import { MVP_BEAM_CALCULATION_CONFIGURATION } from './beam-calculation-configuration';

@Component({
  selector: 'app-beam-form',
  templateUrl: './beam-form.html',
})
export class BeamForm {
  readonly configuration = MVP_BEAM_CALCULATION_CONFIGURATION;
}
