---
name: upsert-process-bpmn
description: Create, update, or fetch a ProcessMaker process by name from portable BPMN under projects/{slug}/bpmn/. Uses ProcessMaker core deploy API.
---

# Upsert or get process BPMN

Process `name` = BPMN filename without extension. Git BPMN keeps `${screen:…}`, `${script:…}`, `${process:…}`.

## Push

`POST /api/1.0/deploy-process` — body: `name`, `bpmn`, optional `description`

For `projects/{slug}/bpmn/*.xml`, the CLI loads `projects/{slug}/process-description.txt` when present (override with `--description`).

BC alias: `POST /api/1.0/package-skeleton/deploy-process`.

```bash
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php upsert projects/{slug}/bpmn/{name}.xml
```

## Pull

```bash
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php get {name} --output projects/{slug}/bpmn/{name}.xml
```

Order: upsert-script-php → upsert-screen-yaml → this skill.
