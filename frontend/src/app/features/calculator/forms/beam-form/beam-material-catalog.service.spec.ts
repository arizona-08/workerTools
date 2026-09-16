import { BeamMaterialCatalog } from './beam-material-catalog.service';

describe('BeamMaterialCatalog', () => {
  it('represents the two V1 Beam submodules returned by the backend', () => {
    const catalog: BeamMaterialCatalog = {
      concreteClasses: [],
      steelGrades: [],
      reinforcementBarDiameters: [],
      exposureClasses: [],
      beamSubmodules: [
        { id: 'BEAM_SIMPLE_RECTANGULAR', label: 'Poutre rectangulaire simplement appuyée', status: 'AVAILABLE', supportSystem: 'SIMPLY_SUPPORTED' },
        { id: 'BEAM_CANTILEVER_RECTANGULAR', label: 'Poutre rectangulaire en console', status: 'AVAILABLE', supportSystem: 'CANTILEVER' },
      ],
    };

    expect(catalog.beamSubmodules).toEqual([
      { id: 'BEAM_SIMPLE_RECTANGULAR', label: 'Poutre rectangulaire simplement appuyée', status: 'AVAILABLE', supportSystem: 'SIMPLY_SUPPORTED' },
      { id: 'BEAM_CANTILEVER_RECTANGULAR', label: 'Poutre rectangulaire en console', status: 'AVAILABLE', supportSystem: 'CANTILEVER' },
    ]);
  });

  it('keeps future unavailable statuses representable without a selector', () => {
    const status: BeamMaterialCatalog['beamSubmodules'][number]['status'] = 'COMING_SOON';
    const unavailable: BeamMaterialCatalog['beamSubmodules'][number]['status'] = 'UNAVAILABLE';

    expect(status).toBe('COMING_SOON');
    expect(unavailable).toBe('UNAVAILABLE');
  });
});
