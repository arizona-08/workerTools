@if ($section->items !== [] || $section->steps !== [] || $section->subsections !== [])
  <section class="avoid-break">
    <h2>{{ $section->title }}</h2>
    @if ($section->items !== [])<table><tbody>@foreach ($section->items as $item)<tr><th>{{ $item->label }}</th><td>{{ $presentation->value($item) }}</td></tr>@endforeach</tbody></table>@endif
    @foreach ($section->steps as $step)<div class="formula"><strong>{{ $step->name }}</strong>@if ($step->formula !== null)<br>{{ $step->formula }}@endif @if ($step->substitution !== null)<br>{{ $step->substitution }}@endif @if ($step->result !== null)<br>Résultat : {{ $presentation->scalar($step->result) }}@endif</div>@endforeach
    @foreach ($section->subsections as $subsection) @include('pdf.partials.calculation-note-section', ['section' => $subsection, 'presentation' => $presentation]) @endforeach
  </section>
@endif
