# Projects Structure

This plugin can contain multiple projects.

Do not invent tool names or a third deploy CLI.

## Before creating anything

Ask only if missing:

1. **Slug** (kebab-case): e.g. `project-name`
2. **Pascal name**: e.g. `ProjectName`
3. **Table prefix** (short, unique, like Military `amil_`): e.g. `ahs_`

## Target tree

Replace `{slug}` / `{Pascal}` / `{prefix}`. Full tree: [structure.md](structure.md).

```
ai-docs/{slug}/
  AGENTS.md
  architecture/database.md
  architecture/workflow.md
  architecture/frontend.md

projects/{slug}/bpmn/{slug}-transcript.xml
projects/{slug}/bpmn/{slug}-applicant.xml
projects/{slug}/scripts/{slug}-{action}.php
# ProjectName: projects/{slug}/scripts/{project-name}/{title}.php
projects/{slug}/tce-{slug}/{process-name}/{screen-name}.yaml

src/Projects/{Pascal}/
  Domain/Entities/
  Domain/Schemas/
  Application/
  Infrastructure/

resources/js/projects/{slug}/{screenFolder}/{ScreenName}.vue

tests/Unit/Projects/{Pascal}/
tests/Unit/Documentation/
```

## Workflow

Copy this checklist and complete it in order:

```
- [ ] 1. Folders + ai-docs index
- [ ] 2. Hexagonal PHP skeleton (empty dirs + Shared Model only)
- [ ] 3. Thin ProcessMaker scripts (first use case only when there is a script; empty skeleton is OK)
- [ ] 4. YAML screens + Vue + Storybook
- [ ] 5. BPMN XML with ${screen:title} and ${script:title} (no numeric refs in git)
- [ ] 6. Publish: upsert-script-php, then upsert-screen-yaml, then upsert-process-bpmn
- [ ] 7. If new src/ classes are used from a script, rebuild the php-nayra executor
- [ ] 8. Record process_id / screen_id / script_id in workflow.md (not in git BPMN refs)
- [ ] 9. PHPUnit for use cases + script wrappers
- [ ] 10. Link root AGENTS.md
```

### 1. Folders + ai-docs

Create `ai-docs/{slug}/` the same way as `ai-docs/high-school/` (domain index + architecture docs).

`AGENTS.md` must list:

- Architecture doc links
- BPMN files and `DB_ID` / `process_id` (fill after upsert-process-bpmn)
- `pm:screenRef` → YAML + Vue path
- `pm:scriptRef` → `projects/{slug}/scripts/{slug}-*.php` → Application use case (ProjectName: `projects/{slug}/scripts/{slug}-title.php`)

### 2. Hexagonal PHP

Create `src/Projects/{Pascal}/` per [hexagonal.md](hexagonal.md). Namespace: `ProcessMaker\Package\PackagePlg\Projects\{Pascal}`. Existing PSR-4 (`PackagePlg\` → `src`) covers it; do not add a new autoload root.

**Skeleton** is empty folders (`Domain/Entities`, `Domain/Schemas`, `Domain/Ports`, `Application/UseCases`, `Infrastructure/Persistence`). Do not invent a dummy `Student` entity. First use case appears when there is a script task: `{Action}` under `Application/UseCases/` with `$data` in / array out. No port until there is DB/HTTP. Shape: [hexagonal.md](hexagonal.md).

- Domain: entities extending shared `Projects\Shared\Domain\Model` **when there is a real entity**. Ports only when there is I/O. Schemas in `Domain/Schemas/`. See [models.md](models.md).
- Application: one use case per script task (replaces Military `*Actions` statics). Constructor-inject ports **if** the use case talks to DB/HTTP. Search Shared/Military before writing a new class; extract duplicated I/O to a Shared port. Verb+object names and purpose docblocks — see [hexagonal.md](hexagonal.md).
- Infrastructure: RedBean adapters implementing ports. Copy `ScriptTaskContainer` + `app()` helper only if this domain already uses that pattern (Military). Prefer wrapper `isset` + `new UseCase($adapter)` or `new UseCase()` with no deps. Never Laravel `app()` from script tasks.

HTTP/Eloquent stays in `src/Http` and `src/Models/{Pascal}` only for package APIs. Script tasks must not call those models. Domain entities must extend `ProcessMaker\Package\PackagePlg\Projects\Shared\Domain\Model`, never a new project-local base and never Eloquent.

Table names: `{prefix}_*` (Military: `amil_*`). Document them in `architecture/database.md`.

### 3. Scripts

- Filename basename **equals** the ProcessMaker script **title**.
- kebab-case: `{slug}-{action}.php`. Optional `-se` suffix for Smart Extract. Keep legacy titles (`APP - …`) unchanged.
- Thin wrapper: global `$data` in, return array. Constructor-inject ports when there is I/O. PHPUnit `include`s the wrapper and may set port fakes (`isset(...)`).

```php
<?php

/**
 * ProcessMaker script: {slug}-{action}
 *
 * BPMN scriptTask: {human label} ({process}).
 * Thin wrapper: global $data in, merged process array out.
 * Logic: {UseCase}. I/O: none, or port + RedBean/Guzzle adapter (not Laravel Http).
 */

use ProcessMaker\Package\PackagePlg\Projects\{Pascal}\Application\UseCases\{Action};

return (new {Action}())->handle($data);
```

When the use case needs a port, construct the adapter in the wrapper (`isset($transcripts) && $transcripts instanceof TranscriptRepository ? $transcripts : new RedBeanTranscriptRepository()`) only if that port **and** adapter exist under `src/Projects/`. Do not import a Shared port class that is not in the tree. Second copy of the same I/O → extract Shared then.

### 4. Screens (YAML + Vue)

**Version in git as YAML**, not Screen Builder JSON dumps.

- File: `documentation/tce-{slug}/{process-name}/{kebab-screen}.yaml`
- A domain-specific root Vue component composed from local child components. The root owns domain coordination; child components own distinct controls or sections.
- Every screen item must include an item-level `inspector: []` when no inspector fields are defined; Screen Builder validation expects this array.
- Use one `VueComponentRenderTemplate` for a simple screen, or expose multiple direct components/items when Screen Builder must insert or replace controls between sections.
- Vue root: `resources/js/projects/{slug}/{camelOrPascalFolder}/{ScreenName}.vue`
- Local children: `components/{Domain}{Section}.vue`; import them locally for internal composition. In `public-screen-components.json`, the parent entry must declare its direct children in nested `components`, and every child must also have an independent top-level `{ name, key }` entry. Repeat recursively for nested child dependencies. Register only the root with `registerPublicScreenComponent` in the applicable FORM/DISPLAY Screen Builder entrypoints and package-wide when the screen is used outside Screen Builder.
- Reuse `resources/js/projects/shared/` first (CAT1 in military frontend.md), but assume each domain has independent business logic. Similar screens are references, not implementations.
- Before coding, document the screen extension points: which sections are code-owned, slot-based, direct `contentVue` components, or separate Screen Builder items.
- New UUID per YAML control (`uuidgen`).
- `handlerVue` maps process `$data` into the Vue `v-model`; guard `this.data` because Screen Builder may evaluate the handler before transient process data exists.

Do not use legacy `APP - APV3 - ...` screen titles.

### 5. BPMN

- `{slug}-transcript.xml` — TCO reviews extracted transcript
- `{slug}-applicant.xml` — application / articulation (if in scope)
- Reuse `documentation/bpmn/upload-transcript.xml` via CallActivity when upload is needed


### Screen quality gate

Before publishing or handing off a new screen:

- Run ESLint on modified Vue/JavaScript source.
- Run relevant Storybook interaction tests for loading, empty, error, success, and submit/cancel behavior.
- After Storybook verification, list each relevant story as a markdown link `[ExportName](http://127.0.0.1:7007/?path=/story/<storyId>)`. See root `AGENTS.md` → *After verifying Storybook (frontend handoff)*.
- Review SonarQube findings introduced in `resources/js/projects/**`; fix new Blocker, Critical, and Major issues.
- Keep complexity, duplication, unused code, unsafe nested access, and unhandled API failures out of new code.
- Guard `this.data` in `handlerVue` because the Screen Builder editor may evaluate it before transient process data exists.
- Do not use broad SonarQube exclusions or unexplained lint suppressions; document any unavoidable suppression.
- Include validation commands and pre-existing findings in the PR description.

## Naming

| Asset | Convention | Example |
| --- | --- | --- |
| Domain slug | kebab-case | `high-school` |
| PHP path / namespace | `src/Projects/{Pascal}/` | `ProcessMaker\Package\PackagePlg\Projects\HighSchool` |
| Domain model base | extend shared `Model` | `ProcessMaker\Package\PackagePlg\Projects\Shared\Domain\Model` |
| JSON Schema | `Domain/Schemas/{entity}.schema.json` | `src/Projects/HighSchool/Domain/Schemas/student.schema.json` |
| BPMN file | `{slug}-{purpose}.xml` | `high-school-transcript.xml` |
| Script file/title | `{slug}-{action}.php` | `high-school-execute-transcript-actions.php` |
| Screen YAML | `{slug}-{purpose}.yaml` | `high-school-transcript.yaml` |
| Vue folder | camelCase or Pascal matching peers | `transcriptHighSchool/` |
| Storybook | `Projects/{Pascal}/...` | `Projects/HighSchool/Transcript` |
| DB tables | `{prefix}_*` | `ahs_transcripts` |
| PHP locals | camelCase words, not loop letters | `$institutionId` not `$instCode` / `$x` |

## Do not

- Put new hexagonal PHP in `src/{Pascal}/` or `src/Military/` (use `src/Projects/{Pascal}/`)
- Add a project-local `*Model` base; extend `Projects\Shared\Domain\Model`
- Put JSON schemas under `src/Models/{Pascal}/Schemas/` for new domains
- Put domain logic only inside `documentation/scripts/*.php`
- Call Laravel Eloquent from script tasks
- Store screens only in ProcessMaker (YAML is the versioned artifact)
- Store processes only in Modeler (BPMN XML is the versioned artifact)
- Patch numeric `pm:scriptRef` / `pm:screenRef` into git BPMN after deploy
- Create ad-hoc Storybook top-level groups
- Write skills or docs under `~/.cursor/skills-cursor/`


## Storybook organization

Run Storybook with Node 20+:
`npm install`
`npm run storybook`

All story entries should be grouped using the `title` field as the canonical structure.

Keep named story exports in each `*.stories.js` file in alphabetical order by
export name. Keep the default metadata export at the top; sort only the named
stories that follow it.

Use these Storybook top-level groups:
- `Components`: shared reusable UI components.
  - Keep legacy `UI` stories under `Components/UI/*`.
- `Projects`: domain-specific stories grouped under:
  - `Projects/{ProjectName}/*`

Aim to keep stories close to their implementation area, and prefer moving any story into the most specific group above rather than creating ad-hoc top-level groups.

### After verifying Storybook (frontend handoff)

When frontend changes are verified with Storybook (interaction tests or manual
checks), list each relevant story as a **markdown link** whose label is the
story export name and whose target opens that story. Do not paste bare URLs
beside the name.

- Dev server: `npm run storybook` → `http://127.0.0.1:7007`
- Link form: `[ExportName](http://127.0.0.1:7007/?path=/story/<storyId>)`
- Resolve `<storyId>` from the running index when available
  (`http://127.0.0.1:7007/index.json` → `entries[].id`), or from the CSF
  `title` + export name in Storybook kebab-case
  (e.g. title `Projects/CollegeV2/Applicant/CollegeCourseEvaluation` + export
  `AutoSelectSentToStudent` →
  `projects-collegev2-applicant-collegecourseevaluation--auto-select-sent-to-student`).

Example handoff:

- [AutoSelectSentToStudent](http://127.0.0.1:7007/?path=/story/projects-collegev2-applicant-collegecourseevaluation--auto-select-sent-to-student)
- [SendCourseToStudent](http://127.0.0.1:7007/?path=/story/projects-collegev2-applicant-collegecourseevaluation--send-course-to-student)

## PHPUnit development
If you need to run a phpunit test prefer to use:
```
/usr/local/bin/phpunit vendor/processmaker/package-skeleton/{test_path}
```

Test script tasks could have some special behavior, it usually expect to have a global variable $data and return an array, some tests for script task can be found at `tests/Unit/Documentation`

## Cross-domain architecture

- [Best code practices](./ai-docs/best-code-practices.md): **SonarQube must pass** on changed code, cognitive complexity ≤ 15, classes ≤ 20 methods, and related quality expectations.
- [Custom Vue Components](./ai-docs/custom-vue-components.md): administration, Screen Builder runtime resolution, browser caching, security, and Screen Import/Export dependencies.
- [Projects hexagonal organization](./ai-docs/hexagonal.md)
- [Projects assets organization](./projects/AGENTS.md)

## Projects Domain-Specific Instructions

Each project have its particular structure, refer to:

- [project-name](./ai-docs/{project-name}/AGENTS.md)
