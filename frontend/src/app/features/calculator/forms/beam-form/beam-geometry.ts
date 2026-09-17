import { BeamCalculationConfigurationView } from './beam-calculation-configuration';
import { BeamLongitudinalReinforcementPayload } from './beam-longitudinal-reinforcement';

export type BeamGeometryFormValue = {
  effectiveSpan: number;
  width: number;
  height: number;
};

export type BeamGeometryPayload = {
  effectiveSpan: number;
  width: number;
  height: number;
  unit: 'mm';
};

export type BeamCalculationPayload = {
  configuration: BeamCalculationConfigurationView;
  geometry: BeamGeometryPayload;
  materials: BeamMaterialsPayload;
  loads: BeamLoadsPayload;
  reinforcement?: BeamLongitudinalReinforcementPayload;
};

export type BeamMaterialsPayload = {
  concreteClass: string;
  steelGrade: string;
  /** Le domaine conserve une liste pour une future extension multi-exposition. */
  exposureClasses: string[];
};

export type BeamLoadsPayload = {
  permanent: BeamPermanentLoadsPayload;
  variable: BeamVariableLoadPayload;
};

export type BeamPermanentLoadsPayload = {
  includeSelfWeight: boolean;
  additionalPermanentLoad: number;
  unit: 'kN/m';
};

export type BeamVariableLoadPayload = {
  category: 'A';
  characteristicLoad: number;
  unit: 'kN/m';
};

const MILLIMETRES_PER_CENTIMETRE = 10;
const MILLIMETRES_PER_METRE = 1000;

export function buildBeamGeometryPayload(value: BeamGeometryFormValue): BeamGeometryPayload {
  return {
    effectiveSpan: value.effectiveSpan * MILLIMETRES_PER_METRE,
    width: value.width * MILLIMETRES_PER_CENTIMETRE,
    height: value.height * MILLIMETRES_PER_CENTIMETRE,
    unit: 'mm',
  };
}
