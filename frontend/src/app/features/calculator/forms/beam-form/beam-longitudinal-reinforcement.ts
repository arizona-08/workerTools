export type BeamLongitudinalReinforcementPayload = {
  longitudinal: {
    tension: {
      barCount: number;
      barDiameter: number;
      diameterUnit: 'mm';
    };
  };
};

export function calculateProvidedSteelArea(barCount: number, barDiameter: number): number {
  return barCount * Math.PI * barDiameter ** 2 / 4;
}
