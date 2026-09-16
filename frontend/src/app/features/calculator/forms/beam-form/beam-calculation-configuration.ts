/** Contrat UI du seul périmètre actuellement supporté par le module Poutre. */
export type BeamCalculationMode = 'DESIGN' | 'VERIFICATION';
export type BeamSubmodule = 'BEAM_SIMPLE_RECTANGULAR' | 'BEAM_CANTILEVER_RECTANGULAR';
export type BeamSupportSystem = 'SIMPLY_SUPPORTED' | 'CANTILEVER';

export interface BeamCalculationConfigurationView {
  calculationMode: BeamCalculationMode;
  elementType: 'BEAM';
  materialType: 'REINFORCED_CONCRETE';
  sectionType: 'RECTANGULAR';
  /** Optional only to keep inputs created before BEAM-SUB-01 compatible. */
  submodule?: BeamSubmodule;
  supportSystem: BeamSupportSystem;
  loadModel: 'UNIFORMLY_DISTRIBUTED';
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR';
  designSituation: 'PERSISTENT_TRANSIENT';
}

export const SUPPORTED_BEAM_CALCULATION_CONFIGURATION: BeamCalculationConfigurationView = {
  calculationMode: 'DESIGN',
  elementType: 'BEAM',
  materialType: 'REINFORCED_CONCRETE',
  sectionType: 'RECTANGULAR',
  submodule: 'BEAM_SIMPLE_RECTANGULAR',
  supportSystem: 'SIMPLY_SUPPORTED',
  loadModel: 'UNIFORMLY_DISTRIBUTED',
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR',
  designSituation: 'PERSISTENT_TRANSIENT',
};
