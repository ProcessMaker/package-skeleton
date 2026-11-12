---
name: upsert-screen-yaml
description: Create, update, or fetch a ProcessMaker screen by title from YAML under projects/{slug}/screens/.
---

# Upsert or fetch screen YAML

Title = YAML basename. Auth via package root `.env` (`PM_BASE_URL`, `PM_SYNC_TOKEN`).

## Push

`POST /api/1.0/{package-slug}/deploy-screen` — body: `name`, `yml_content`

```bash
php .agents/skills/upsert-screen-yaml/scripts/upsert-screen.php projects/{slug}/screens/{title}.yaml
```

## Pull

`GET /api/1.0/{package-slug}/screen-yaml?name={title}`

```bash
php .agents/skills/upsert-screen-yaml/scripts/get-screen.php projects/{slug}/screens/{title}.yaml
```

Publish **after** scripts, **before** process BPMN.
