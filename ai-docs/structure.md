# Project folder structure

Canonical layout for a new domain `{slug}` / `{Pascal}` / table prefix `{prefix}`.

## Documentation (agents + humans)

```
ai-docs/{slug}/
├── AGENTS.md
└── architecture/
    ├── database.md      # {prefix}_* tables, FKs
    ├── workflow.md      # BPMN path, tasks, screens, scripts, gateways
    └── frontend.md      # domain inventory: roots/children, screens table, theme (template: ai-docs/_template/)
```

- **Package-wide** Screen Builder mechanics (catalog, Vite bundles, publish): [screen-builder.md](./screen-builder.md).
- **Per slug:** copy `ai-docs/_template/` to `ai-docs/{slug}/` and fill `architecture/frontend.md`; link `screen-builder.md` instead of duplicating build steps.

Mirror High School: a dedicated `ai-docs/{slug}/` tree, not a single mega-file. Index every new architecture doc from `AGENTS.md`.

Root `AGENTS.md` must link `./ai-docs/{slug}/AGENTS.md`.

## ProcessMaker artifacts (versioned)

```
projects/{slug}/
├── bpmn/
│   └── {slug}-{purpose}.xml
├── scripts/
│   ├── {slug}-script-name.php
│   └── {slug}-other-script.php
└── screens/
    ├── {slug}-screen.yaml
    └── {slug}-other-screen.yaml
```

## PHP (hexagonal)

Namespace: `ProcessMaker\Package\PackageSkeleton\Projects\{Pascal}\...` (renamed with the package).

```
src/Projects/Shared/Domain/Model.php   # JsonSerializable + optional JSON Schema validation
src/Traits/ValidatesWithJsonSchema.php
src/Projects/Shared/Domain/Ports/      # only after a second domain copies the same I/O
src/Projects/Shared/Infrastructure/    # adapters for those Shared ports
src/Projects/Shared/Application/UseCases/  # scripts shared across two+ domains

src/Projects/{Pascal}/
├── Domain/
│   ├── Entities/                      # extend Shared\Domain\Model
│   ├── ValueObjects/
│   └── Ports/                         # interfaces only; skip until I/O exists
├── Application/
│   ├── UseCases/                      # one class per script task
│   └── Dto/
└── Infrastructure/
    ├── Persistence/                   # Adapters implementing ports
    └── Services/                      # Service Tasks classes

src/Models/{Pascal}/                   # Eloquent for HTTP/API only
database/migrations/                   # {prefix}_* tables
```

## Vue + Storybook

Domain Vue uses **camelCase** folder names under `resources/js/projects/` (skeleton: `packageSkeleton`; `rename-project.php` rewrites to e.g. `demoLeave`).

```
resources/js/designer/
├── public-screen-components.json
├── screen-builder-form-components.js
└── screen-builder-display-components.js
resources/js/componentsDesigner/customComponents.js
resources/js/projects/{camelSlug}/
├── shared/                            # domain CAT2 only if reused by 2+ screens
└── {screenFolder}/
    ├── {ScreenName}.vue
    ├── {ScreenName}.stories.js        # title: Projects/{Pascal}/...
    ├── components/
    ├── composables/
    ├── api/
    └── config/
```

Package-wide shared UI: `resources/js/projects/shared/`.

## Tests

```
tests/Unit/Projects/{Pascal}/          # use cases, adapters
tests/Unit/Documentation/              # script wrappers with $data
tests/Feature/                         # HTTP only
```

Run:

```bash
/usr/local/bin/phpunit vendor/processmaker/package-skeleton/{test_path}
```
