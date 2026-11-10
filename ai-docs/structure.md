# TCE project folder structure

Canonical layout for a new domain `{slug}` / `{Pascal}` / table prefix `{prefix}`.

## Documentation (agents + humans)

```
ai-docs/{slug}/
├── AGENTS.md
└── architecture/
    ├── database.md      # {prefix}_* tables, FKs
    ├── workflow.md      # BPMN path, tasks, screens, scripts, gateways
    └── frontend.md      # Vue screens and shared components used
```

Mirror High School: a dedicated `ai-docs/{slug}/` tree, not a single mega-file. Index every new architecture doc from `AGENTS.md`.

Root `AGENTS.md` must link `./ai-docs/{slug}/AGENTS.md`.

## ProcessMaker artifacts (versioned)

```
projects/{slug}/
├── bpmn/
│   └── {slug}-process.bpmn          # omit if not in scope
├── scripts/
│   ├── {slug}-script-name.php
│   └── {slug}-other-script.php
└── screens/
    ├── {slug}-screen.yaml
    └── {slug}-other-screen.yaml
```

## PHP (hexagonal)

Namespace: `ProcessMaker\Package\PackageName\Projects\{Pascal}\...`

```
src/Projects/Shared/Domain/Model.php   # JsonSerializable + ValidatesWithJsonSchema (always)
src/Projects/Shared/Domain/Ports/      # only after a second domain copies the same I/O
src/Projects/Shared/Infrastructure/    # adapters for those Shared ports
src/Projects/Shared/Application/UseCases/  # APP scripts shared across two+ domains

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

```
resources/js/projects/{slug}/
├── shared/                            # domain CAT2 only if reused by 2+ screens
└── {screenFolder}/
    ├── {ScreenName}.vue
    ├── {ScreenName}.stories.js        # title: Projects/{Pascal}/...
    ├── components/
    ├── composables/
    ├── api/
    └── config/
```

Shared CAT1: `resources/js/projects/shared/`. Dashboards that are not domain-specific stay in `resources/js/dashboards/`.

## Tests

```
tests/Unit/Projects/{Pascal}/          # use cases, adapters
tests/Unit/Documentation/              # script wrappers with $data
tests/Feature/                         # HTTP only
```

Run:

```bash
PROCESSMAKER_HOME=/path/to/processmaker XDEBUG_MODE=off php ./vendor/bin/phpunit --configuration phpunit.xml --no-coverage {test_path}
```
