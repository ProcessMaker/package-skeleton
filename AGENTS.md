# Projects Structure

This plugin can contain multiple projects.

Do not invent tool names or a third deploy CLI.

**Wizard option 1 (Migration):** after `composer create-project`, follow [ai-docs/migration/AGENTS.md](ai-docs/migration/AGENTS.md). MCP + `.env` are configured the same as option 2 (PM4 only).

## Before creating anything

Ask only if missing:

1. **Slug** (kebab-case): e.g. `project-name`
2. **Pascal name**: e.g. `ProjectName`
3. **Table prefix** (short, unique): e.g. `ahs_`

## Target tree

Replace `{slug}` / `{Pascal}` / `{prefix}`. Full tree: [ai-docs/structure.md](ai-docs/structure.md).

```
ai-docs/{slug}/
  AGENTS.md
  architecture/database.md
  architecture/workflow.md
  architecture/frontend.md

projects/{slug}/bpmn/{slug}-{purpose}.xml
projects/{slug}/scripts/{slug}-{action}.php
projects/{slug}/screens/{slug}-{purpose}.yaml

src/Projects/{Pascal}/
  Domain/Entities/
  Domain/Schemas/
  Application/
  Infrastructure/

resources/js/projects/shared/                        # plugin-wide, reused by 2+ projects
resources/js/projects/{camelSlug}/shared/            # project-wide, reused by 2+ screens
resources/js/projects/{camelSlug}/{screenFolder}/{ScreenName}.vue
resources/js/projects/{camelSlug}/{screenFolder}/components/

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
- [ ] 6. Beautify the created/updated bpmn processes
- [ ] 7. Publish (see §6): package on PM4 first, then upsert-script-php → upsert-screen-yaml → upsert-process-bpmn
- [ ] 8. Record process_id / screen_id / script_id in workflow.md (not in git BPMN refs)
- [ ] 9. PHPUnit for use cases + script wrappers
- [ ] 10. Link root AGENTS.md
- [ ] 11. Coverage report: follow [ai-docs/report.md](ai-docs/report.md)
```

### 1. Folders + ai-docs

Create `ai-docs/{slug}/` (domain index + architecture docs). See [ai-docs/structure.md](ai-docs/structure.md).

`AGENTS.md` must list:

- Architecture doc links
- BPMN files and `DB_ID` / `process_id` (fill after upsert-process-bpmn)
- `pm:screenRef` → YAML + Vue path
- `pm:scriptRef` → `projects/{slug}/scripts/{slug}-*.php` → Application use case

### 2. Hexagonal PHP

Create `src/Projects/{Pascal}/` per [ai-docs/hexagonal.md](ai-docs/hexagonal.md). Namespace: `ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}`. Existing PSR-4 (`PackageSkeleton\` → `src`) covers it; do not add a new autoload root. After `rename-project.php`, `PackageSkeleton` becomes the package Pascal name.

**Skeleton** is empty folders (`Domain/Entities`, `Domain/Schemas`, `Domain/Ports`, `Application/UseCases`, `Infrastructure/Persistence`) plus shared base [Model](src/Projects/Shared/Domain/Model.php). Do not invent a dummy entity. First use case appears when there is a script task: `{Action}` under `Application/UseCases/` with `$data` in / array out. No port until there is DB/HTTP. Shape: [ai-docs/hexagonal.md](ai-docs/hexagonal.md). Entities: [ai-docs/models.md](ai-docs/models.md).

- Domain: entities extending shared `Projects\Shared\Domain\Model` **when there is a real entity**. Ports only when there is I/O. Schemas in `Domain/Schemas/`.
- Application: one use case per script task. Constructor-inject ports **if** the use case talks to DB/HTTP. Search `src/Projects/Shared` before writing a new class; extract duplicated I/O to a Shared port.
- Infrastructure: RedBean (or HTTP) adapters implementing ports. Prefer wrapper `isset` + `new UseCase($adapter)` or `new UseCase()` with no deps. Never Laravel `app()` from script tasks.

HTTP/Eloquent stays in `src/Http` and `src/Models/{Pascal}` only for package APIs. Script tasks must not call those models. Domain entities must extend `ProcessMaker\Package\PackageSkeleton\Projects\Shared\Domain\Model`, never a new project-local base and never Eloquent.

Table names: `{prefix}_*`. Document them in `architecture/database.md`.

### 3. Scripts

- Filename basename **equals** the ProcessMaker script **title**.
- kebab-case: `{slug}-{action}.php`. Optional `-se` suffix for Smart Extract.
- Thin wrapper: global `$data` in, return array. Constructor-inject ports when there is I/O. PHPUnit `include`s the wrapper and may set port fakes (`isset(...)`).

```php
<?php

/**
 * ProcessMaker script: {slug}-{action}
 *
 * BPMN scriptTask: {human label} ({process}).
 * Thin wrapper: global $data in, merged process array out.
 * Logic: {UseCase}. I/O: none, or port + adapter (not Laravel Http).
 */

use ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}\Application\UseCases\{Action};

return (new {Action}())->handle($data);
```

When the use case needs a port, construct the adapter in the wrapper (`isset($port) && $port instanceof PortInterface ? $port : new RedBeanAdapter()`) only if that port **and** adapter exist under `src/Projects/`. Use RedBean or Guzzle adapters from `Infrastructure/` — not Laravel `Http` or Eloquent from script tasks.

### 4. Screens (YAML + Vue)

**Version in git as YAML**, not Screen Builder JSON dumps.

- File: `projects/{slug}/screens/{kebab-screen}.yaml`
- A domain-specific root Vue component composed from local child components. The root owns domain coordination; child components own distinct controls or sections.
- Every screen item must include an item-level `inspector: []` when no inspector fields are defined; Screen Builder validation expects this array.
- Use one `VueComponentRenderTemplate` for a simple screen, or multiple direct components/items when Screen Builder must insert or replace controls between sections.
- Vue layout follows [§4.1 Vue layout](#41-vue-layout-plugin--project--screen); register components per [§4.2](#42-register-vue-components).
- Before coding, document the screen extension points: which sections are code-owned, slot-based, direct `contentVue` components, or separate Screen Builder items (see [custom-vue-components.md](ai-docs/custom-vue-components.md)).
- New UUID per YAML control (`uuidgen`).
- `handlerVue` maps process `$data` into the Vue `v-model`; guard `this.data` because Screen Builder may evaluate the handler before transient process data exists.
- Every screen YAML includes a non-empty `preview_presets` list. Each item is `title` plus `data`, and `data` is a JSON **string** (`'{}'` or `'{"field":"value"}'`), not a nested mapping. Cover empty/start, the happy path, and each major UI branch. `upsert-screen-yaml` persists them; Screen Builder → Preview switches between them.

Do not use legacy `APP - APV3 - ...` screen titles.

Package reference: [ai-docs/screen-builder.md](ai-docs/screen-builder.md). Per-project Vue inventory: [ai-docs/_template/architecture/frontend.md](ai-docs/_template/architecture/frontend.md) → `ai-docs/{slug}/architecture/frontend.md`.

#### 4.1 Vue layout (plugin → project → screen)

A plugin (this package) holds several projects; each project is one process (`projects/{slug}/`). Vue code mirrors that hierarchy with three levels of reuse:

Concrete layout (`project-name` / intake screen). `shared` and `components` are literal directory names:

```
resources/js/projects/
├── shared/                              # plugin-wide, only when 2+ projects use it
└── projectName/
    ├── shared/components/               # only when 2+ screens of this project use it
    └── intake/
        ├── ProjectNameIntake.vue        # root mounted from YAML contentVue
        ├── ProjectNameIntake.stories.js # Storybook: Projects/ProjectName/Intake
        └── components/                  # local children, only when one exists
            └── ProjectNameIntakeHeader.vue
```

- Screen mapping: `projects/{slug}/screens/{slug}-{purpose}.yaml` → `resources/js/projects/{camelSlug}/{purposeCamel}/{Pascal}{PurposePascal}.vue`. Example: `project-name-intake.yaml` → `resources/js/projects/projectName/intake/ProjectNameIntake.vue`.
- **Create a directory only when a file goes inside it.** Do not scaffold empty `components/`, `composables/`, `api/`, or `styles/`. A screen with no local child is just the root `.vue` and its story.
- **Never create a directory named `{components}`, `{shared}`, or `{composables}`.** Those words are not placeholders. On zsh, `mkdir {components}` does not expand and the braces become part of the name. Write `mkdir components`.
- Start every component inside its screen folder. Promote it to `{camelSlug}/shared/` when a second screen of the same project needs it, and to `projects/shared/` when a second project needs it. Shared components stay generic; business rules stay in the project.
- Similar screens in other projects are references, not implementations: do not import another project's screen folder; promote the piece to `projects/shared/` instead.
- Root components own domain coordination; children own distinct controls or sections (`{Pascal}{Section}.vue`).
- **Vue components created while authoring screens with the ProcessMaker MCP (or any skill) always go in this package's project folder above.** Never create or register them in `package-plg` or any other package; the Screen Builder control, catalog, and bundles ship with this package.

#### 4.2 Register Vue components

`resources/js/componentsDesigner/customComponents.js` bundles every `resources/js/projects/**/*.vue`; the catalog decides which ones Screen Builder exposes. After adding or changing components:

1. Register each component used from YAML in `resources/js/designer/public-screen-components.json` as `{ "name", "key" }`, with `key` = `@/projects/{camelSlug}/{screenFolder}/{ScreenName}.vue` (or the `shared/` path).
2. When a root declares children for Screen Builder, list its **direct** children in a nested `components` array **and** add a separate top-level `{ name, key }` row for each child. Repeat recursively for nested children. Children imported only inside a `.vue` file do not need catalog rows.
3. Build: `npm run prod` from the package root (Vite main build + FORM/DISPLAY IIFE bundles).
4. Publish on the ProcessMaker host: `php artisan vendor:publish --tag={package-name} --force`, then hard-refresh Screen Builder.
5. Only then create or update the screen (`upsert-screen-yaml` or Screens MCP) with `components` entries copied from the catalog. A missing key fails at runtime with `No SFC bundled for <key>`.

The catalog is loaded by `ensureScreenComponentRegistry()` in this package's screen-builder bundles (see [screen-builder.md](ai-docs/screen-builder.md)); `package-plg` is not required on the server.

### 5. BPMN

- Prefer readable top-level phases; put phase detail inside embedded `bpmn:subProcess` when helpful.
- **CallActivity** only when reusing another published process or needing a separate child request.
- Prefer [pm4-process](.agents/skills/pm4-process/SKILL.md) + [pm4-sub-process](.agents/skills/pm4-sub-process/SKILL.md) when those skills are installed (create-project wizard).

### 6. Publish

**Precondition:** target ProcessMaker core exposes deploy sync (`POST /api/1.0/deploy-script|deploy-screen|deploy-process`). Upsert CLIs call those core endpoints (BC aliases under `/api/1.0/package-skeleton/deploy-*` also work). Set `PM_BASE_URL` and `PM_SYNC_TOKEN` in the package repo `.env` (wizard or `.env.example`).

Order (skills under `.agents/skills/`):

1. `upsert-script-php` — `projects/{slug}/scripts/{title}.php`
2. `upsert-screen-yaml` — `projects/{slug}/screens/{title}.yaml`
3. `upsert-process-bpmn` — `projects/{slug}/bpmn/{name}.xml` (portable `${screen:}` / `${script:}` / `${process:}`)

MCP (`/mcp/screens`, `/mcp/processes`) is optional for authoring; git → instance sync uses the core deploy endpoints above unless the user asked for MCP-only publish.

### Screen quality gate

Before publishing or handing off a new screen:

- Run ESLint on modified Vue/JavaScript source.
- Run relevant Storybook interaction tests for loading, empty, error, success, and submit/cancel behavior.
- After Storybook verification, list each relevant story as a markdown link `[ExportName](http://127.0.0.1:7007/?path=/story/<storyId>)`.
- Review SonarQube findings introduced in `resources/js/projects/**`; fix new Blocker, Critical, and Major issues.
- Guard `this.data` in `handlerVue`.
- Do not use broad SonarQube exclusions or unexplained lint suppressions.

## Naming

| Asset | Convention | Example |
| --- | --- | --- |
| Domain slug | kebab-case | `project-name` |
| PHP path / namespace | `src/Projects/{Pascal}/` | `ProcessMaker\Package\PackageSkeleton\Projects\ProjectName` |
| Domain model base | extend shared `Model` | `ProcessMaker\Package\PackageSkeleton\Projects\Shared\Domain\Model` |
| JSON Schema | `Domain/Schemas/{entity}.schema.json` | `src/Projects/ProjectName/Domain/Schemas/record.schema.json` |
| BPMN file | `{slug}-{purpose}.xml` | `project-name-intake.xml` |
| Script file/title | `{slug}-{action}.php` | `project-name-save-record.php` |
| Screen YAML | `{slug}-{purpose}.yaml` | `project-name-intake.yaml` |
| Vue project folder | camelCase of slug | `resources/js/projects/projectName/` |
| Vue screen folder | camelCase of YAML purpose | `resources/js/projects/projectName/intake/` |
| Vue screen root | `{Pascal}{PurposePascal}.vue` | `ProjectNameIntake.vue` |
| Catalog key | `@/projects/{camelSlug}/...` | `@/projects/projectName/intake/ProjectNameIntake.vue` |
| Storybook | `Projects/{Pascal}/...` | `Projects/ProjectName/Intake` |
| DB tables | `{prefix}_*` | `ahs_records` |
| PHP locals | camelCase words, not loop letters | `$institutionId` not `$instCode` / `$x` |

## Do not

- Put new hexagonal PHP outside `src/Projects/{Pascal}/`
- Add a project-local `*Model` base; extend `Projects\Shared\Domain\Model`
- Put JSON schemas under `src/Models/{Pascal}/Schemas/` for new domains
- Put domain logic only inside unversioned ProcessMaker UI assets
- Call Laravel Eloquent from script tasks
- Store screens only in ProcessMaker (YAML is the versioned artifact)
- Store processes only in Modeler (BPMN XML is the versioned artifact)
- Patch numeric `pm:scriptRef` / `pm:screenRef` into git BPMN after deploy
- Create ad-hoc Storybook top-level groups
- Create or register screen Vue components in `package-plg` (or any package other than this one)
- Import one project's screen components from another project; promote to `resources/js/projects/shared/`
- Write skills or docs under `~/.cursor/skills-cursor/`

## Storybook organization

Run Storybook with Node 20+:
`npm install`
`npm run storybook`

All story entries should be grouped using the `title` field as the canonical structure.

Keep named story exports in each `*.stories.js` file in alphabetical order by export name. Keep the default metadata export at the top; sort only the named stories that follow it.

Use these Storybook top-level groups:

- `Components`: shared reusable UI components.
- `Projects`: domain-specific stories under `Projects/{ProjectName}/*`

### After verifying Storybook (frontend handoff)

List each relevant story as a markdown link whose label is the story export name:

- Dev server: `npm run storybook` → `http://127.0.0.1:7007`
- Link form: `[ExportName](http://127.0.0.1:7007/?path=/story/<storyId>)`
- Resolve `<storyId>` from `http://127.0.0.1:7007/index.json` → `entries[].id`, or from CSF `title` + export name in Storybook kebab-case.

## PHPUnit development

Prefer:

```
/usr/local/bin/phpunit vendor/processmaker/package-skeleton/{test_path}
```

Script tasks usually expect a global `$data` and return an array. Script wrapper tests live under `tests/Unit/Documentation` when present.

## Cross-domain architecture

- [Best code practices](./ai-docs/best-code-practices.md): **SonarQube must pass** on changed code, cognitive complexity ≤ 15, classes ≤ 20 methods, and related quality expectations.
- [Screen Builder package bundles](./ai-docs/screen-builder.md): catalog, Vite IIFE builds, publish path (no `package-plg` dependency).
- [Custom Vue Components](./ai-docs/custom-vue-components.md): administration, Screen Builder runtime resolution, browser caching, security, and Screen Import/Export dependencies.
- [Projects hexagonal organization](./ai-docs/hexagonal.md)
- [Domain models](./ai-docs/models.md)
- [Projects assets organization](./projects/AGENTS.md)

## Projects Domain-Specific Instructions

Each project has its own structure under:

- [project-name](./ai-docs/{project-name}/AGENTS.md)
