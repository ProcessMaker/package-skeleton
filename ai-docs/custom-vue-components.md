# Custom Vue components

Optional capability for packages created from Package Skeleton. Implement routes and controllers in **your** package slug (`{package-slug}`); this skeleton documents the pattern only — it does not ship the full administration stack by default.

> **Package skeleton:** each PM4 package embeds `VueComponentRenderTemplate`, the catalog, and screen-builder IIFE builds locally. See [screen-builder.md](./screen-builder.md). The notes below apply to runtime behavior; administration DB APIs may live in optional packages such as `package-plg`.

## Purpose

Allows administrators to replace selected Vue components used directly by a ProcessMaker Screen without modifying or rebuilding the official component. It also allows administrators to create DB-only Vue components for use in `contentVue`.

Screen configuration example:

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

If an enabled customization exists for the key, it is registered locally in the dynamic template; otherwise Vue resolves the official global component.

## Core invariants

- `key` is the stable identity of a component (`@/…vue` or `custom://slug`).
- Screens store `name` and `key` only — never a custom component ID, UUID, or content hash.
- Customization is local to each `VueComponentRenderTemplate` instance.

## Catalog (code-owned components)

Register the catalog once per Screen Builder entrypoint (FORM, DISPLAY, and any designer admin bundle you add):

```js
import registerPublicScreenCatalog from './registerPublicScreenCatalog';

registerPublicScreenCatalog();
```

Canonical catalog path in git:

```text
resources/js/designer/public-screen-components.json
```

Parent entries declare direct children in nested `components`; every child also needs a top-level `{ name, key }` entry. Register only roots with `registerPublicScreenComponent` in the applicable entrypoints. See root [AGENTS.md](../AGENTS.md) §4 Screens.

## Screen Builder configuration

The `VueComponentEditorConfig` exposes a `components` property (`{name, key}` entries). The explicit list is the source of truth for tags referenced from `contentVue` — do not infer dependencies by parsing template HTML alone.

`handlerVue` maps process `$data` into the Vue model; guard `this.data` because the editor may evaluate handlers before transient process data exists.

## Optional package API

When you implement custom Vue administration in a derived package, use your API prefix (after rename, e.g. `package-my-app`):

```text
GET    /api/1.0/{package-slug}/custom-vue-components
GET    /api/1.0/{package-slug}/custom-vue-components/catalog
GET    /api/1.0/{package-slug}/custom-vue-components/available
```

Place controllers under `src/Http/Controllers/Api/` and register routes in `routes/api.php` with the same `{package-slug}` segment as deploy-script/screen/process.

## Runtime resolution (summary)

1. Normalize the Screen `components` configuration.
2. Prefer in-memory definitions (`register` / `registerPreview`) when present.
3. Otherwise resolve the official catalog component for the key.
4. Local registrations must not leak across Screens.

Designer Live Preview can use `registerPreview` for unsaved editor buffers. See ProcessMaker Screen Builder `customComponents.js` and `RenderTemplate.js` in the host application when wiring advanced behavior.

## Limitation

Replacements apply only to components referenced from `contentVue`, not to children imported lexically inside another `.vue` file. Expose explicit extension points when nested replacement is required.

Further cross-cutting rules: [best-code-practices.md](./best-code-practices.md).
