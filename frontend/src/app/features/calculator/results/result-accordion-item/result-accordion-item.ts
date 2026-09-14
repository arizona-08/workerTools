import { ChangeDetectionStrategy, Component, input, signal } from '@angular/core';
import { LucideChevronDown } from '@lucide/angular';

/** Accordéon de présentation réutilisable, accessible et sans logique métier. */
@Component({
  selector: 'app-result-accordion-item',
  imports: [LucideChevronDown],
  templateUrl: './result-accordion-item.html',
  styleUrl: './result-accordion-item.css',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ResultAccordionItem {
  readonly title = input.required<string>();
  readonly contentId = input.required<string>();
  readonly initiallyExpanded = input(false);
  readonly expanded = signal(false);

  ngOnInit(): void {
    this.expanded.set(this.initiallyExpanded());
  }

  toggle(): void {
    this.expanded.update((expanded) => !expanded);
  }
}
