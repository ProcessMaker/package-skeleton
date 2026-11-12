# {slug} frontend

Domain-specific Vue and Screen Builder inventory. **Build, catalog, and publish:** [screen-builder.md](../../screen-builder.md).

Vue 2 + BootstrapVue. Official tags: `resources/js/designer/public-screen-components.json`.

## Components

| Story group (`Projects/{Pascal}/…`) | Root component | Children (catalog / local) |
| --- | --- | --- |
| _(example)_ Dashboard | `{Pascal}Dashboard` | `{Section}Cards`, `{Section}Table` |
| _(example)_ Form | `{Pascal}RequestForm` | — |

Add one row per root SFC used in YAML `components`. Children listed here should match nested `components` in the catalog for that root.

## Screens (optional)

If not already in `ai-docs/{slug}/AGENTS.md`:

| Screen title | YAML | Vue root |
| --- | --- | --- |
| `{slug}-screen-name` | `projects/{slug}/screens/{slug}-screen-name.yaml` | `resources/js/projects/{camelSlug}/…/{Root}.vue` |

## Screen Builder

FORM/DISPLAY YAML uses `VueComponentRenderTemplate` with `contentVue`, `handlerVue`, and catalog `components`. Guard `this.data` in handlers (see [screen-builder.md](../../screen-builder.md)).

## Theme and shared UI

- Shared tokens/CSS: `resources/js/projects/{camelSlug}/shared/` _(describe file, e.g. `{slug}Theme.css`)_
- Reuse `resources/js/projects/shared/` only when the same control is truly cross-domain (CAT1).

## Build (this package)

```bash
npm run prod
php artisan vendor:publish --tag={package-name} --force
```

Replace `{package-name}` with the Composer package name (kebab-case, e.g. `demo-leave`).
