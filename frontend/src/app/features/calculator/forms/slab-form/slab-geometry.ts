export type SlabGeometryFormValue = {
  effectiveSpan: number;
  thickness: number;
};

/** Contrat interne prêt pour le futur moteur Dalle, sans largeur fournie par le client. */
export type SlabGeometryPayload = {
  effectiveSpan: number;
  thickness: number;
  unit: 'mm';
};

const MILLIMETRES_PER_CENTIMETRE = 10;
const MILLIMETRES_PER_METRE = 1000;

/** Conversion unique des unités de saisie UI vers les longueurs internes. */
export function buildSlabGeometryPayload(value: SlabGeometryFormValue): SlabGeometryPayload {
  return {
    effectiveSpan: value.effectiveSpan * MILLIMETRES_PER_METRE,
    thickness: value.thickness * MILLIMETRES_PER_CENTIMETRE,
    unit: 'mm',
  };
}
