<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 18mm 15mm 18mm; }
    body { font-family: "DejaVu Sans", sans-serif; color: #18181b; font-size: 9pt; line-height: 1.45; }
    h1 { margin: 0; color: #2e1065; font-size: 18pt; } h2 { margin: 18pt 0 7pt; border-bottom: 1px solid #ddd6fe; color: #4c1d95; font-size: 12pt; padding-bottom: 3pt; }
    h3 { margin: 10pt 0 4pt; font-size: 10pt; } p { margin: 3pt 0; }
    .brand { color: #6d28d9; font-size: 10pt; font-weight: bold; } .meta { margin-top: 10pt; color: #52525b; }
    .status { display: inline-block; margin-top: 8pt; padding: 4pt 7pt; border: 1px solid #a78bfa; color: #2e1065; font-weight: bold; }
    .status--not-compliant { border-color: #dc2626; color: #991b1b; } .status--not-checked, .status--method-not-supported { border-color: #d97706; color: #92400e; }
    table { width: 100%; border-collapse: collapse; margin: 5pt 0 10pt; } th, td { border: 1px solid #e4e4e7; padding: 4pt 5pt; text-align: left; vertical-align: top; } th { width: 42%; background: #fafafa; color: #3f3f46; font-weight: normal; } .verification th { width: auto; background: #f5f3ff; font-weight: bold; }
    .avoid-break { page-break-inside: avoid; } .formula { overflow-wrap: anywhere; font-family: "DejaVu Sans Mono", monospace; font-size: 8pt; } .notice { border-left: 3pt solid #d97706; background: #fffbeb; margin: 6pt 0; padding: 6pt 8pt; } .limitations { border-left-color: #6d28d9; background: #faf5ff; }
    .footer { position: fixed; bottom: -11mm; left: 0; right: 0; color: #71717a; font-size: 8pt; text-align: center; }
  </style>
</head>
<body>
  <header>
    <p class="brand">WorkerTools</p>
    <h1>{{ $document->metadata->title }}</h1>
    <p class="meta">{{ $presentation->calculationType($document->metadata->calculationType) }} · {{ $document->metadata->generatedAt->format('d/m/Y H:i') }} · {{ $presentation->identifier($document->metadata->designCodeProfile) }}</p>
    <p class="status status--{{ strtolower(str_replace('_', '-', $document->finalStatus->status->value)) }}">Statut final : {{ $presentation->status($document->finalStatus->status) }}</p>
    @if ($document->finalStatus->governingVerificationType !== null || $document->finalStatus->governingUtilization !== null)
      <p class="meta">
        @if ($document->finalStatus->governingVerificationType !== null)Vérification gouvernante : {{ $presentation->identifier($document->finalStatus->governingVerificationType) }}@endif
        @if ($document->finalStatus->governingVerificationType !== null && $document->finalStatus->governingUtilization !== null) · @endif
        @if ($document->finalStatus->governingUtilization !== null)Taux d’utilisation : {{ $presentation->scalar($document->finalStatus->governingUtilization * 100) }} %@endif
      </p>
    @endif
    @if ($document->finalStatus->summary !== [])
      <table><tbody>@foreach ($document->finalStatus->summary as $item)<tr><th>{{ $item->label }}</th><td>{{ $presentation->value($item) }}</td></tr>@endforeach</tbody></table>
    @endif
  </header>

  @include('pdf.partials.calculation-note-section', ['section' => $document->assumptions, 'presentation' => $presentation])
  @include('pdf.partials.calculation-note-section', ['section' => $document->geometry, 'presentation' => $presentation])
  @include('pdf.partials.calculation-note-section', ['section' => $document->materials, 'presentation' => $presentation])
  @include('pdf.partials.calculation-note-section', ['section' => $document->loads, 'presentation' => $presentation])
  @if ($document->combinations !== null) @include('pdf.partials.calculation-note-section', ['section' => $document->combinations, 'presentation' => $presentation]) @endif
  @if ($document->internalForces !== null) @include('pdf.partials.calculation-note-section', ['section' => $document->internalForces, 'presentation' => $presentation]) @endif

  @if ($document->verifications !== [])
    <section><h2>Vérifications</h2>@foreach ($document->verifications as $verification)<article class="avoid-break"><h3>{{ $verification->label }}</h3><table class="verification"><tr><th>Statut</th><td>{{ $presentation->status($verification->status) }}</td></tr>@if ($verification->utilization !== null)<tr><th>Utilisation</th><td>{{ $presentation->scalar($verification->utilization * 100) }} %</td></tr>@endif @if ($verification->governingValue !== null)<tr><th>Valeur gouvernante</th><td>{{ $presentation->scalar($verification->governingValue) }} {{ $verification->unit }}</td></tr>@endif @if ($verification->limitValue !== null)<tr><th>Limite</th><td>{{ $presentation->scalar($verification->limitValue) }} {{ $verification->unit }}</td></tr>@endif @if ($verification->method !== null)<tr><th>Méthode</th><td>{{ $presentation->identifier($verification->method) }}</td></tr>@endif @foreach ($verification->details as $detail)<tr><th>{{ $detail->label }}</th><td>{{ $presentation->value($detail) }}</td></tr>@endforeach</table></article>@endforeach</section>
  @endif

  @if ($document->reinforcement !== [])
    <section><h2>Ferraillage proposé</h2><table><thead><tr><th>Élément</th><th>Désignation</th><th>Aire fournie</th><th>Aire requise</th></tr></thead><tbody>@foreach ($document->reinforcement as $reinforcement)<tr><td>{{ $reinforcement->label }}</td><td>{{ $presentation->reinforcementDesignation($reinforcement) }}</td><td>{{ $presentation->scalar($reinforcement->providedArea) }} {{ $reinforcement->unit }}</td><td>{{ $presentation->scalar($reinforcement->requiredArea) }} {{ $reinforcement->unit }}</td></tr>@endforeach</tbody></table></section>
  @endif

  @if ($document->warnings !== [])<section class="notice"><strong>Avertissements</strong><ul>@foreach ($document->warnings as $warning)<li>{{ $presentation->identifier($warning) }}</li>@endforeach</ul></section>@endif
  @if ($document->limitations !== [])<section class="notice limitations"><strong>Limitations</strong><ul>@foreach ($document->limitations as $limitation)<li>{{ $limitation }}</li>@endforeach</ul></section>@endif
  <footer class="footer">WorkerTools · Note de calcul · {{ $document->metadata->generatedAt->format('d/m/Y') }}</footer>
</body>
</html>
