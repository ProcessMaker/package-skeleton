# Custom Vue Components

## Purpose

Package PLG allows administrators to replace selected Vue components used directly by a ProcessMaker Screen without modifying or rebuilding the official component. It also allows administrators to create DB-only Vue components for use in `contentVue`.

The customization boundary is `VueComponentRenderTemplate`. Normal Vue development continues to use static imports. Internal components imported locally by an official component are not replaced.

Example:

```html
<CustomizableSummaryCard
  title="Application summary"
  :items="data.summaryItems"
  @action="submit"
/>
```

The Screen declares the component identity:

```json
{
  "components": [
    {
      "name": "CustomizableSummaryCard",
      "key": "@/components/screen-builder/examples/CustomizableSummaryCard.vue"
    }
  ]
}
```

If an enabled customization exists for the key, that component is registered locally in the dynamic template. Otherwise Vue resolves the official global component.

## Core invariants

- `key` is the stable, unique identity of a component.
- A key has at most one current customization in `custom_vue_components`.
- Screens store `name` and `key`; they never store a custom component ID, UUID, hash, or revision.
- An official component uses an `@/…vue` key and has a code-based fallback.
- A DB-only component uses a `custom://slug` key and has no official fallback.
- `content_hash` is only a cache identifier. It is not a functional revision selected by a Screen.
- If revision history is added later, the component owns the single active revision. Screens must continue referencing only the key.
- Customization is local to a `VueComponentRenderTemplate` instance. Never overwrite a component globally at runtime.

## Official and DB-only components

### Official component

Official components are listed in the catalog and registered by `registerPublicScreenCatalog`. Each entry is `{ name, key }` plus an optional `components` array of direct `{ name, key }` children. The loader bundles only those catalog files and `registerPublicScreenComponent` wraps an entry that declares children so those tags resolve when the SFC renders. A customization registered for a child key replaces the official child at render time.

```js
import registerPublicScreenCatalog from './registerPublicScreenCatalog';

registerPublicScreenCatalog();
```

FORM, DISPLAY, and the administration page call that helper. Do not enumerate `registerPublicScreenComponent` in those entrypoints.

The canonical catalog is stored in:

```text
resources/js/designer/public-screen-components.json
```

Both the FORM/DISPLAY entrypoints and the backend administration page consume this catalog. Only components intentionally designed for the no-build runtime belong in it. `CustomizableSummaryCard` is the reference implementation: it has no imports, uses globally available BootstrapVue components, plain Vue 2 JavaScript, Bootstrap classes, and plain CSS.

### DB-only component

A component authored entirely in the administration UI uses a key such as:

```text
custom://student-summary
```

It must be declared with both name and key in the Screen configuration. If it is missing, disabled, or invalid, rendering fails because no official implementation exists.

## Data model

Migration:

```text
database/migrations/2026_09_15_000000_create_custom_vue_components_table.php
```

Model:

```text
src/Models/CustomVueComponent.php
```

Important fields:

| Field | Meaning |
|---|---|
| `uuid` | Import/Export asset identity; unrelated to a Screen UUID |
| `key` | Unique runtime identity (`@/...vue` or `custom://slug`) |
| `name` | Vue tag/component name used in `contentVue` |
| `template` | Vue 2 runtime template |
| `script` | Trusted function body inlined into the public asset `factory`, e.g. `return { props: {} }` |
| `style` | Optional CSS injected once per key |
| `preview_template` | Optional example markup rendered only in the administration preview |
| `preview_script` | Optional Vue options function body for the example wrapper |
| `content_hash` | SHA-256 over executable component content |
| `enabled` | Whether runtime manifests may expose the customization |

The model recomputes `content_hash` on every save. Preview fields are intentionally excluded because they are documentation for the administration UI and are not included in the public runtime asset. The database enforces unique `uuid` and unique `key`.

## Administration

Web page:

```text
GET /designer/custom-components
resources/views/custom-components/index.blade.php
```

It is linked from the Designer sidebar and protected by `view-settings`. Mutations require `edit-settings`.

Authenticated API under the package API prefix (`/api/1.0`):

```text
GET    /package-plg/custom-vue-components
GET    /package-plg/custom-vue-components/catalog
GET    /package-plg/custom-vue-components/catalog/{name}/source
GET    /package-plg/custom-vue-components/{component}
POST   /package-plg/custom-vue-components
PUT    /package-plg/custom-vue-components/{component}
DELETE /package-plg/custom-vue-components/{component}
```

Controller:

```text
src/Http/Controllers/Api/CustomVueComponentController.php
```

The source endpoint reads only allowlisted catalog entries, extracts Vue SFC blocks, and converts `export default` to the runtime `return` format. It reports imports and module loading as blocking diagnostics. It rejects `<script setup>`, TypeScript, and CSS preprocessors because they require a build step. The administration editor uses Monaco and prevents saving while runtime compatibility errors remain.

The Properties tab stores `preview_script`, the Vue options function body for the example wrapper (`handlerVue`). Live Preview still renders through `VueComponentRenderTemplate` using an auto-generated `preview_template` of `<ComponentName v-bind="props" />` (kept in sync when the component name changes). The wrapper receives the edited component through the normal `components` configuration, registered both under its configured name and under the `PreviewComponent` alias. On the HTML tab, the left side of the `Uses` bar is the saved dependency list (`custom_vue_components.components`, also emitted in the runtime asset). The right side shows catalog tags found in the template that are not in that list; `+` moves one into the saved list. Detected tags are not stored until added. Official `components` stay in the catalog JSON; the Screen's own `config.components` is still required for tags used directly by `contentVue`. The preview panel is resizable and can be expanded to full screen.

Because the renderer resolves in-memory definitions first, the page primes the runtime with the unsaved editor buffer:

```text
window.ProcessMakerCustomComponents.registerPreview(key, definition)
window.ProcessMakerCustomComponents.clearPreview(key)
```

`registerPreview` stores the definition and drops any previously injected style for that key. `clearPreview` reverses both. Preview therefore exercises the real resolution path while still showing unsaved changes. Preview fields participate in Import/Export but do not change the runtime asset hash.

The administration page loads `VueComponentRenderTemplate` from a dedicated bundle so it does not depend on the Screen Builder entrypoints:

```text
resources/js/designer/custom-components-admin.js
public/js/custom-components-admin.js
```

That bundle calls `registerPublicScreenCatalog`, like the FORM and DISPLAY entrypoints, so preview dependencies resolve by key on the administration page too. A bare `Vue.component()` there would leave `publicComponents` empty and only the global tag reachable.

Valid keys begin with `@/` or `custom://`. Vue component names must be PascalCase-compatible.

## Screen Builder configuration

The `VueComponentEditorConfig` defines a `components` property. At runtime it may arrive as an array or JSON string; the resolver accepts both.

`VueComponentsInspector` manages this property as `{name, key}` entries. Add uses a searchable suggest dropdown of unused catalog items. The inspector lists tags already on the Screen: `name` is the read-only Content Vue tag, and `key` is a combobox that replaces which catalog component that tag loads without rewriting the template. Changing a key keeps the name. Keys already used by other rows are omitted so each catalog component maps to at most one tag. Catalog metadata comes from `GET /api/1.0/package-plg/custom-vue-components/available` (requires `view-screens`), which combines the registered catalog and enabled DB components, deduplicated by key. Official names remain the defaults when a customization shares their key. Removing a row (with confirm) does not edit Content Vue; leftover tags no longer resolve. Add uses the catalog name, or a numeric suffix when that tag is already taken. Existing JSON configurations remain readable; new edits emit arrays. This list only covers tags declared on the `VueComponentRenderTemplate` instance, not children inside an official SFC template.

Relevant files:

```text
resources/js/designer/componentsConfig.js
resources/js/components/screen-builder/freeEditor/VueComponentEditor.vue
resources/js/components/screen-builder/freeEditor/RenderTemplate.js
```

`RenderTemplate.js` creates a dynamic Vue component with:

```js
{
  components: resolveTemplateComponents(configuredComponents),
  template: contentVue,
  mixins: [handlerVueMixin],
}
```

Local registrations take precedence over the official global registrations and prevent one Screen's customization from leaking into another Screen.

Do not infer dependencies by parsing `contentVue`. The explicit `components` array is the source of truth because templates may use aliases or `<component :is="…">`.

## Runtime resolution

Runtime implementation:

```text
resources/js/components/screen-builder/freeEditor/customComponents.js
```

Resolution sequence:

1. Normalize the Screen's `components` configuration.
2. If an in-memory definition exists for the key (`register` / `registerPreview`), build it with `factory()`, `template`, `style`, and any nested `components`, and register it locally under the configured `name`.
3. Otherwise register the official bundle component for that key directly under that `name`.
4. If the key has neither a definition nor an official component, register nothing for it and let Vue resolve the tag through its global registrations.
5. If building a custom official component fails, log the error and return the official component.

Step 4 matters because in Vue 2 a local registration wins even when its async factory rejects. Declaring an unresolvable key locally would blank out a tag that a global `Vue.component` could still render, which is how a `Uses` entry for an official component used to erase children from Live Preview.

The client does not fetch `/package-plg/custom-components/manifest` or load hashed asset scripts. Screen-time replacement of a saved DB customization therefore requires the definition to already be registered in memory. Designer Live Preview continues to work through `registerPreview`.

### Important limitation

This mechanism replaces only components referenced directly from `contentVue`. It does not replace a child imported locally inside another `.vue` file:

```js
import PdfViewer from './PdfViewer.vue';

export default {
  components: { PdfViewer },
};
```

Replacing `PdfViewer` globally cannot override this lexical/local registration. The parent must expose an explicit extension point if that behavior is ever required.

## Public assets (unused by the current client)

The server still exposes:

```text
GET /package-plg/custom-components/manifest?keys[]=...
GET /package-plg/custom-components/assets/{contentHash}
```

Controller: `src/Http/Controllers/CustomVueComponentAssetController.php`. The Screen Builder runtime no longer calls these endpoints.

These endpoints intentionally have no authentication. They expose executable UI definitions, never Screen IDs, case data, secrets, or user data. The administrative CRUD remains authenticated and permission-controlled.

Asset URLs intentionally have no `.js` extension so static-file rules in Nginx do not bypass Laravel. The response still uses the JavaScript MIME type and immutable cache headers.

## Import and export

Core ProcessMaker is not modified. Package PLG extends the core `ScreenExporter` from its service provider:

```text
src/PackageServiceProvider.php
src/ImportExport/ScreenExporterExtension.php
src/ImportExport/CustomVueComponentExporter.php
src/Services/CustomComponentsInScreen.php
```

`CustomComponentsInScreen` recursively traverses `Screen::config`, including pages, loops, and multi-column nesting. For every `VueComponentRenderTemplate`, it extracts `config.components[*].key`.

`ScreenExporterExtension::preExport()` adds existing custom components as `custom-vue-component` dependents, matched by the Screen's registered keys. Enabled and disabled customizations are both exported so the destination keeps the same `enabled` value. Official components without a DB row are not exported because their implementation is already in package code.

`CustomVueComponentExporter` uses:

```php
public static $fallbackMatchColumn = ['key'];
```

Import matching order is UUID, then unique key. Because a key may have only one customization, valid conflict outcomes are:

- update the destination customization; or
- discard the imported customization and retain the destination value.

Never create a copied key such as `CustomizableSummaryCard 2`; the imported Screen still references the canonical original key. No post-import Screen rewrite is required because Screens reference stable keys rather than database IDs.

## Security model

Custom component JavaScript is trusted administrator-authored code and executes with the current user's browser permissions. The browser must not evaluate a script string from JSON, query parameters, or an arbitrary URL.

Runtime transport:

- The public asset inlines the admin script as a `factory` function body. The client calls `definition.factory()` and never uses `eval` or `new Function` for custom components.
- `name`, `template`, and `style` remain JSON-encoded with `JSON_HEX_TAG` and `JSON_HEX_AMP`.
- Script tags are injected only for same-origin paths `/package-plg/custom-components/assets/{64-char-sha256}` whose hash matches the manifest.
- Screen `components` keys must match `@/` or `custom://` plus a conservative identifier. Vue names must be PascalCase identifiers.
- `handlerVue` is still a Screen Builder designer mixin evaluated with `new Function`. It is not request input; do not reuse that path for custom component transport.

Development rules:

- Never accept custom component writes from anonymous or ordinary users.
- Keep CRUD behind `view-settings`/`edit-settings` or stronger dedicated permissions.
- Never place secrets or tenant data in public component assets.
- Preserve `X-Content-Type-Options: nosniff` and the JavaScript MIME type.
- On failure, official components must fall back safely. DB-only components must fail visibly rather than silently rendering the wrong component.

## Tests and verification

Backend tests:

```text
tests/Unit/Services/CustomComponentsInScreenTest.php
tests/Unit/Services/CustomVueComponentRuntimeScriptTest.php
tests/Unit/ImportExport/CustomVueComponentExporterTest.php
tests/Unit/ImportExport/ScreenExporterExtensionTest.php
tests/Feature/Api/CustomComponents/CustomVueComponentTest.php
```

Frontend story:

```text
resources/js/components/screen-builder/freeEditor/RenderTemplate.stories.js
```

`WithCustomPublicComponent` verifies that a custom local `CustomizableSummaryCard` overrides the official component.

Recommended checks:

```bash
npx eslint resources/js/components/screen-builder/freeEditor/customComponents.js \
  resources/js/components/screen-builder/freeEditor/RenderTemplate.js \
  resources/js/components/screen-builder/freeEditor/RenderTemplate.stories.js --no-fix

npx vite build -c vite.config.form.js
npx vite build -c vite.config.display.js
npm run build-storybook

/usr/local/bin/phpunit /absolute/path/to/test.php
```

Do not run `npm run dev`; repository policy reserves watchers and dev servers for developers.

The ProcessMaker PHPUnit environment currently requires Redis during application/database setup. Pure unit tests can run without it; integration tests require the configured test services.

## Agent change checklist

When adding another official customizable component:

1. Keep its normal static import.
2. Add its name and canonical key to `public-screen-components.json`.
3. Replace its direct `Vue.component()` registration with `registerPublicScreenComponent()`.
4. Pass only the catalog name and component implementation to `registerPublicScreenComponent()`; it resolves the canonical key and rejects names missing from the catalog.
5. Update both FORM and DISPLAY entrypoints if both expose it.
6. Add `name` and `key` to the relevant Screen's `components` configuration.
7. Add or update a Storybook scenario for official fallback and custom precedence.
8. Rebuild tracked FORM/DISPLAY bundles and `public/mix-manifest.json`.
9. Do not add component IDs, UUIDs, hashes, or revisions to Screen configuration.

When changing persistence or Import/Export:

1. Preserve unique `key` semantics.
2. Preserve a model-owned UUID distinct from Screen UUIDs.
3. Preserve matching by key across installations.
4. Update dependency traversal tests for any Screen config shape changes.
5. Keep package-specific dependency discovery out of ProcessMaker core.

When changing runtime transport:

1. Preserve one batch manifest lookup rather than one API request per component.
2. Preserve content-addressed immutable assets.
3. Preserve local Vue registration and official fallback.
4. Verify FORM, DISPLAY, and Storybook builds.
