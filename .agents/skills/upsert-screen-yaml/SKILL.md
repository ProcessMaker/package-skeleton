---
name: upsert-screen-yaml
description: Create, update, or fetch a ProcessMaker screen by title from YAML under projects/{slug}/screens/. Uses ProcessMaker core deploy API.
---

# Upsert or fetch screen YAML

Title = YAML basename. Auth via package root `.env` (`PM_BASE_URL`, `PM_SYNC_TOKEN`).

**Precondition:** every `@/projects/...` key in the YAML `components` exists in this package under `resources/js/projects/{camelSlug}/`, is listed in `resources/js/designer/public-screen-components.json`, and the package was built (`npm run prod`) and published (`vendor:publish --tag={package-name} --force`). See root `AGENTS.md` §4.1–4.2. Never create these components in `package-plg`.

## Push

`POST /api/1.0/deploy-screen` — body: `name`, `yml_content`, and on **update** required `version_name` (maps to `package-versions` **Version History** `subject`, same as the UI “Version Name” on publish)

Top-level metadata (deployed on create/update): `description` (string, required for Designer), `category` or `screen_category_name` (defaults to **Uncategorized** on the host when omitted).

The YAML must include `preview_presets`. Each item is `title` and `data`, and `data` is a quoted JSON string so `{}` stays a string:

```yaml
preview_presets:
  - title: Empty / start
    data: '{}'
  - title: Happy path
    data: '{"field":"value"}'
```

Deploy writes that list onto the screen and its published version. `get-screen.php` returns it. Without this key the screen is saved with no Preview presets.

BC alias: `POST /api/1.0/package-skeleton/deploy-screen`.

```bash
php .agents/skills/upsert-screen-yaml/scripts/upsert-screen.php projects/{slug}/screens/{title}.yaml
```

## Pull

`GET /api/1.0/screen-yaml?name={title}`

```bash
php .agents/skills/upsert-screen-yaml/scripts/get-screen.php projects/{slug}/screens/{title}.yaml
```

Publish **after** scripts, **before** process BPMN.
