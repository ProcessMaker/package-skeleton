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

One package (plugin) holds several projects (processes). Vue mirrors that: plugin-wide shared → project → screen. Folder names are **camelCase** (`project-name` → `projectName`). Rules for promotion and registration: root [AGENTS.md](../AGENTS.md) §4.1–4.2.

`shared` and `components` are literal directory names. Create `components/`, `composables/`, `api/`, or `styles/` only when a file goes inside. Never `mkdir {components}` — zsh keeps the braces in the folder name.

```
resources/js/designer/
├── public-screen-components.json
├── screen-builder-form-components.js
└── screen-builder-display-components.js
resources/js/componentsDesigner/customComponents.js   # bundles resources/js/projects/**/*.vue
resources/js/projects/
├── shared/                            # plugin-wide, reused by 2+ projects
└── projectName/                       # camelCase of the project slug
    ├── shared/components/             # reused by 2+ screens of this project
    └── intake/                        # one folder per screen
        ├── ProjectNameIntake.vue
        ├── ProjectNameIntake.stories.js
        └── components/                # local children, only when one exists
            └── ProjectNameIntakeHeader.vue
```

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
