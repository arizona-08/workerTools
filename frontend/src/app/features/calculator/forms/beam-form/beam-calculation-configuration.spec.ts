import { BeamSubmodule, BeamSupportSystem, SUPPORTED_BEAM_CALCULATION_CONFIGURATION } from './beam-calculation-configuration';

describe('beam calculation configuration', () => {
  it('represents the two synchronized Beam submodules and their support systems', () => {
    const submodules: BeamSubmodule[] = ['BEAM_SIMPLE_RECTANGULAR', 'BEAM_CANTILEVER_RECTANGULAR'];
    const supportSystems: BeamSupportSystem[] = ['SIMPLY_SUPPORTED', 'CANTILEVER'];

    expect(submodules).toEqual(['BEAM_SIMPLE_RECTANGULAR', 'BEAM_CANTILEVER_RECTANGULAR']);
    expect(supportSystems).toEqual(['SIMPLY_SUPPORTED', 'CANTILEVER']);
  });

  it('keeps the existing form default on the simply supported rectangular Beam', () => {
    expect(SUPPORTED_BEAM_CALCULATION_CONFIGURATION.submodule).toBe('BEAM_SIMPLE_RECTANGULAR');
    expect(SUPPORTED_BEAM_CALCULATION_CONFIGURATION.supportSystem).toBe('SIMPLY_SUPPORTED');
  });
});
