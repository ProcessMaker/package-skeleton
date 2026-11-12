---
name: upsert-script-php
description: Create, update, or fetch a ProcessMaker script by title using a PHP file under projects/{slug}/scripts/. Uses this package deploy API or MCP when available. Filename without .php is the script title.
---

# Upsert or fetch script PHP

Git PHP is the source of truth unless the user asked to **pull** from the instance. Lookup is by **title** (basename), never by id.

Auth: Bearer `PM_SYNC_TOKEN`, base `PM_BASE_URL` in package root `.env` (set by create-project wizard). `--host` / `--token` override.

## Push

`POST /api/1.0/{package-slug}/deploy-script`

Body: `name` (title), `code` (full PHP).

Requires this package installed on the ProcessMaker instance (`composer require` + `artisan {slug}:install`).

```bash
XDEBUG_MODE=off php .agents/skills/upsert-script-php/scripts/upsert-script.php projects/{slug}/scripts/{slug}-action.php
```

## Pull

`GET /api/1.0/{package-slug}/script-php?name={title}`

```bash
XDEBUG_MODE=off php .agents/skills/upsert-script-php/scripts/get-script.php projects/{slug}/scripts/{slug}-action.php
```

```bash
XDEBUG_MODE=off php .agents/skills/upsert-script-php/scripts/get-script.php --name {slug}-action
```

Do not reimplement HTTP. Do not `git commit` unless asked.

## Compose

| Next step | Skill |
| --- | --- |
| Screens | [upsert-screen-yaml](../upsert-screen-yaml/SKILL.md) |
| Process BPMN | [upsert-process-bpmn](../upsert-process-bpmn/SKILL.md) |

## Workflow

```
Push:
- [ ] 1. File under projects/{slug}/scripts/*.php
- [ ] 2. basename == ProcessMaker script title
- [ ] 3. Package installed on target PM4
- [ ] 4. Run upsert-script.php
- [ ] 5. Confirm action created or updated and script_id
```
