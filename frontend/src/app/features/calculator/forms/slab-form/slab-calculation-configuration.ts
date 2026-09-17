/** Contrat UI du seul cas Dalle actuellement préparé par le V1. */
export interface SlabCalculationConfigurationView {
  elementType: 'SLAB';
  slabType: 'SOLID';
  spanningSystem: 'ONE_WAY';
  structuralSystem: 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES';
  loadModel: 'VERTICAL_UNIFORMLY_DISTRIBUTED';
  materialType: 'REINFORCED_CONCRETE';
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR';
  designSituation: 'PERSISTENT_TRANSIENT';
}

/** Valeurs FIXED_SCOPE : elles seront étendues explicitement par les prochains tickets Dalle. */
export const SUPPORTED_SLAB_CALCULATION_CONFIGURATION: SlabCalculationConfigurationView = {
  elementType: 'SLAB',
  slabType: 'SOLID',
  spanningSystem: 'ONE_WAY',
  structuralSystem: 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES',
  loadModel: 'VERTICAL_UNIFORMLY_DISTRIBUTED',
  materialType: 'REINFORCED_CONCRETE',
  designCodeProfile: 'NF_EN_1992_1_1_2005_FR',
  designSituation: 'PERSISTENT_TRANSIENT',
};
