# Screen Builder and package frontend (all domains)

Reference for agents and humans: how each ProcessMaker **package** ships FORM/DISPLAY Vue screens without `package-plg` on the server.

Per-domain inventories (which screen maps to which root SFC, Storybook groups, theme CSS) belong in `ai-docs/{slug}/architecture/frontend.md` — start from [`ai-docs/_template/architecture/frontend.md`](./_template/architecture/frontend.md).

## Stack

- Vue 2 + BootstrapVue (Screen Builder host already loads Vue).
- Screens in git are **YAML** with one or more `VueComponentRenderTemplate` controls (use the `pm4-screen` skill on PM4 projects when installed).
- Official screen tags resolve from `resources/js/designer/public-screen-components.json` and are bundled at build time.

## Repository layout

```text
resources/js/designer/
  public-screen-components.json    # catalog { name, key, components? }
  screen-builder-form-components.js
  screen-builder-display-components.js
  componentsConfig.js              # VueComponentRenderTemplate control definition
resources/js/componentsDesigner/
  customComponents.js              # import.meta.glob → resolveDeclaredComponents
  publicScreenComponents.js
resources/js/components/screen-builder/freeEditor/
  RenderTemplate.js
  VueComponentEditor.vue
resources/js/projects/{camelSlug}/   # skeleton: packageSkeleton
public/js/
  screen-builder-form-components.js  # Vite IIFE output
  screen-builder-display-components.js
```

ProcessMaker loads (when the package is installed):

```text
vendor/processmaker/packages/<package-name>/js/screen-builder-form-components.js
vendor/processmaker/packages/<package-name>/js/screen-builder-display-components.js
```

via `ScreenBuilderManager::addComposerPackageScripts` on `ScreenBuilderStarting`.

## Catalog rules

File: `resources/js/designer/public-screen-components.json`

Each **official** component needs a top-level row `{ "name", "key" }`. Keys use the Vite alias: `@/projects/{camelSlug}/path/Component.vue`.

When a root SFC declares local children for Screen Builder (`components` in YAML), the catalog must list:

1. The root, with `"components": [ { "name", "key" }, … ]` for **direct** children only.
2. A **separate** top-level row for every child (and nested catalog child) so the bundle includes that SFC.

The glob in `customComponents.js` must cover every keyed file. A missing file causes runtime error: `No SFC bundled for <key>`.

`rename-project.php` rewrites `packageSkeleton` → `{camelSlug}` (e.g. `demoLeave`) and `package-skeleton` → `{kebab-package}`.

## YAML and handlerVue

Prefer one `VueComponentRenderTemplate` per simple screen: set `contentVue` / `handlerVue` and `components` with `{ name, key }` for catalog roots.

In `handlerVue`, **guard** `this.data` before assigning defaults — Screen Builder may evaluate the handler before transient process data exists:

```javascript
return {
  watch: {
    value: {
      immediate: true,
      handler(v) {
        if (!this.data || typeof this.data !== 'object') return;
        // merge v into this.data…
      },
    },
  },
};
```

Every YAML control item needs `inspector: []` when no inspector fields are defined (Screen Builder validation).

## Build and publish

After changing `.vue` files or `public-screen-components.json`:

```bash
npm install
npm run prod
```

`prod` runs the main package Vite build plus `build:screen-builder` (FORM + DISPLAY IIFE bundles).

On the ProcessMaker host:

```bash
php artisan vendor:publish --tag=<package-name> --force
```

Example: `demo-leave` → `--tag=demo-leave`.

Hard-refresh the browser after publish when debugging `No SFC bundled` or stale controls.

## Screen quality (agents)

Before handoff or publish:

- ESLint on touched `resources/js/**`.
- Storybook under `Projects/{Pascal}/…`; see root `AGENTS.md` → *After verifying Storybook*.
- `handlerVue` guards `this.data`.
- SonarQube on new Vue (Blocker/Critical/Major).

## What goes in domain `frontend.md`

Keep **project-specific** facts in `ai-docs/{slug}/architecture/frontend.md` (use the [template](./_template/architecture/frontend.md)):

| Section | Content |
| --- | --- |
| Components | Table: story group → root Vue → children |
| Screens | Optional: mirror `AGENTS.md` screen title → YAML → Vue path |
| Theme | Domain CSS under `resources/js/projects/{camelSlug}/shared/` |
| Notes | One-off UX or data-binding quirks |

Do **not** duplicate this file’s build/layout/catalog rules in every domain doc — link here instead.

## Related docs

- [structure.md](./structure.md) — folder tree and `ai-docs/{slug}/`
- [custom-vue-components.md](./custom-vue-components.md) — `custom://` overrides, admin boundaries
- [AGENTS.md](../AGENTS.md) — screens checklist and Storybook groups
