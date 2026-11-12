---
name: asset-coverage-report
description: >-
  Write the English coverage report and its shareable PDF when a PM3-to-PM4
  migration finishes creating assets, or when a PM4 process is improved
  (tasks, routing, screens, scripts, notifications). Use when that work
  finishes, the project checklist reaches its last step, or the user asks for
  a coverage report, coverage percentage, open assets, or a PDF to share.
  Not for pm-analyze report, convert, or publish, and not for a one-control edit.
---

# Asset coverage report

Write it in English when either piece of work finishes. One current PDF per domain: overwrite `ai-docs/{slug}/{slug}-coverage.html` and the PDF. Credit Deal is the layout sample, not a source of numbers: [credit-deal-coverage.html](../../../ai-docs/credit-deal-coverage.html).

## Outputs

Write both files. Do not commit unless the user asked.

- `ai-docs/{slug}/{slug}-coverage.html`
- `ai-docs/{slug}/{slug}-coverage.pdf`

Copy the sample HTML (styles, KPI row, stacked bar, tables, callout, footer). Replace every count, name, and sentence. Keep letter size and the print CSS.

## When

Two scenarios. Both write the same files.

**PM3 to PM4.** The migration has produced the domain assets (scripts, YAML screens, BPMN, `ai-docs/{slug}/`), or the root `AGENTS.md` checklist reaches step 11. The baseline is the `.pmx` / `.pmx2` inventory. Title: `{Domain} coverage: PM3 to PM4`.

**PM4 process improvement.** An existing PM4 process changed in a way a user would notice: tasks, routing, screens, scripts, notifications, assignments, or documents. The baseline is that process before the change, plus what this change was asked to do. Title: `{Domain} coverage: PM4 update`. Count only the assets this change touched, and any gap it left open. Do not rebuild the old PM3 inventory unless this change was closing those gaps.

Also run it when the user asks for the coverage report or a PDF to share.

Skip a one-control, copy, or comment edit. Do not run it instead of `pm-analyze report`.

## Count the inventory

For a migration, read the `.pmx` / `.pmx2` (or the saved pm-analyze JSON). For a PM4 update, read the BPMN, screens, and scripts before and after the change. Classify each item as **implemented** (the PM4 artifact now has it), **dropped** (left out on purpose: a PM3-only hack, a test asset, or an event no flow uses), or **open** (still needs a decision or new work). One row per family that has items:

| Family | What to count |
| --- | --- |
| User tasks | One screen or task per PM3 user task |
| Triggers | Ported use case, dropped screen hack, or open |
| Dynaforms | Task screen, section inside a screen, dropped test, or open |
| Grids | Vue grid or computed totals |
| Input documents | Request-file field plus the document index |
| Output documents | Generated PDF or workbook. Missing generator is open |
| Email templates | File copied into the project |
| Assignments | `process_variable` (or equivalent) on the task |
| Object permissions | PM4 permission, or open |
| Report tables | Business tables only. A test table is dropped |
| Supervision | Supervisor steps plus supervisor groups |
| Message start | Dropped when nothing throws it |
| Case tracker | Open unless a public page was built |

Leave process variables out of the denominator. PM4 does not need a variable catalog. Say that in the note under the bar.

Asset percentage = (implemented + dropped) / all rows. Show the three counts beside it.

## Functionality, separate from assets

Score these capabilities from the domain you just built. Do not reuse another domain's percentages.

| Capability | Weight | Closing path |
| --- | --- | --- |
| Task forms | 22 | yes |
| Routing | 14 | yes |
| Ported rules | 16 | yes |
| Notifications | 10 | yes |
| Uploads | 8 | yes |
| PDFs and Excel | 12 | no |
| Assignment | 6 | yes |
| Permissions and supervisors | 4 | no |
| Reports | 4 | yes |
| Plugin dashboard | 4 | no |

Closing-path percent = points of the seven "yes" rows / 80. Full percent = points of all ten rows / 100. Round both to the nearest integer. State both, and say why they differ.

## Sections, in this order

1. Title from the scenario above, source (export file or the PM4 process name), date.
2. Four KPIs: asset percent, closing-path percent, full percent, open count.
3. Family table with implemented, dropped, open, and total.
4. Functionality table with weight, coverage, and one sentence each.
5. What is inside the closing-path percent, and what pulls the full percent down.
6. Open items. Every row names the asset, the count, and why it was not implemented.
7. Dropped on purpose, with why.
8. Implemented work that still needs a manual check (engine walkthrough, visual diff of SQL screens, empty group ids, generated email attachments).
9. Publication. If nothing was published, say so. If it was, list process, screen, and script ids and what the publish did not install (package classes, Vue bundle, migration, seeds).

No secrets, tokens, or personal emails.

## PDF

Print the HTML with headless Chrome. Do not add a second converter.

```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" \
  --headless --disable-gpu --no-first-run --no-pdf-header-footer \
  --print-to-pdf="ai-docs/{slug}/{slug}-coverage.pdf" \
  "file://$PWD/ai-docs/{slug}/{slug}-coverage.html"
```

Confirm the file is a PDF and spot the first page: the four KPIs stay on one line, and the family total matches the KPI. Tell the user the PDF path.
