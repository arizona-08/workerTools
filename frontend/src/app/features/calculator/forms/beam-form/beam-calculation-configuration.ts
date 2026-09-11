/** Contrat UI du seul périmètre actuellement supporté par le module Poutre. */
export type BeamCalculationMode = 'DESIGN' | 'VERIFICATION';

export interface BeamCalculationConfigurationView {
  calculationMode: BeamCalculationMode;
  elementType: 'BEAM';
  materialType: 'REINFORCED_CONCRETE';
  sectionType: 'RECTANGULAR';
  supportSystem: 'SIMPLY_SUPPORTED';
  loadModel: 'UNIFORMLY_DISTRIBUTED';
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR';
  designSituation: 'PERSISTENT_TRANSIENT';
}

export const MVP_BEAM_CALCULATION_CONFIGURATION: BeamCalculationConfigurationView = {
  calculationMode: 'DESIGN',
  elementType: 'BEAM',
  materialType: 'REINFORCED_CONCRETE',
  sectionType: 'RECTANGULAR',
  supportSystem: 'SIMPLY_SUPPORTED',
  loadModel: 'UNIFORMLY_DISTRIBUTED',
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR',
  designSituation: 'PERSISTENT_TRANSIENT',
};
