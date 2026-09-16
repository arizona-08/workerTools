import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DetailValueList } from './detail-value-list';

describe('DetailValueList', () => {
  let fixture: ComponentFixture<DetailValueList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({ imports: [DetailValueList] }).compileComponents();
    fixture = TestBed.createComponent(DetailValueList);
    fixture.componentRef.setInput('values', {
      cover: {
        structuralClassModifiers: [{ rule: 'Réduction pour résistance élevée', value: -1 }],
        exposureResults: [{ exposureClass: 'XC1', minimumDurabilityCover: 10 }],
      },
      stirrups: {
        acceptedCandidates: [{ barDiameter: 8, legCount: 2, spacing: 150, accepted: true }],
        rejectedCandidates: [{ barDiameter: 8, legCount: 2, spacing: 300, rejectionReasons: ['INSUFFICIENT_HORIZONTAL_SPACE'] }],
      },
    });
    fixture.detectChanges();
  });

  it('renders object arrays as expandable labelled values rather than object stringification', () => {
    const text = fixture.nativeElement.textContent;

    expect(text).toContain('Afficher les 2 valeurs');
    expect(text).not.toContain('[object Object]');
    expect(fixture.nativeElement.querySelectorAll('details')).toHaveLength(4);
  });

  it('keeps object-array properties understandable when expanded', () => {
    const details = fixture.nativeElement.querySelectorAll('details') as NodeListOf<HTMLDetailsElement>;
    details.forEach((element) => element.open = true);
    fixture.detectChanges();

    const text = fixture.nativeElement.textContent;
    expect(text).toContain('Réduction pour résistance élevée');
    expect(text).toContain('Enrobage minimal de durabilité');
    expect(text).toContain('Espacement des étriers');
    expect(text).toContain('Largeur disponible insuffisante');
  });

  it('never exposes an untranslated camel-case key when a future detail is not in the dictionary yet', () => {
    fixture.componentRef.setInput('values', { unknownBackendValue: 1 });
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Paramètre : donnée donnée valeur');
    expect(fixture.nativeElement.textContent).not.toContain('unknownBackendValue');
  });
});
