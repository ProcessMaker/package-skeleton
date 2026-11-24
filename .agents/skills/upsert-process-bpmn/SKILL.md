---
name: upsert-process-bpmn
description: Create, update, or fetch a ProcessMaker process by name from portable BPMN under projects/{slug}/bpmn/. Uses ProcessMaker core deploy API.
---

# Upsert or get process BPMN

Process `name` = BPMN filename without extension. Git BPMN keeps `${screen:…}`, `${script:…}`, `${process:…}`.

## Push

`POST /api/1.0/deploy-process` — on **update**, required `version_name` → Version History `subject` (package-versions)

For `projects/{slug}/bpmn/*.xml`, the CLI loads `projects/{slug}/process-description.txt` when present (override with `--description`) and `process-category.txt` / `--category` for `process_category_name` (default **Uncategorized**).

BC alias: `POST /api/1.0/package-skeleton/deploy-process`.

```bash
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php upsert projects/{slug}/bpmn/{name}.xml
```

## Alternative B

Add `--alternative B` to write the BPMN to **Alternative B** of an existing process. The live process (Alternative A) is never changed. Without `--publish` it saves the B draft; with `--publish` it publishes a B version and links it to the A/B record (`process_version_b_id`). Requires package-ab-testing and a process that already exists (deploy it as A first). `version_name` is not needed for B.

```bash
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php upsert projects/{slug}/bpmn/{name}.xml --alternative B --publish
```

Publishing B does **not** route cases to it: A/B routing stays as configured. Check the routing expression before switching (see the `pm4-process-refactor` skill, "Run a case on B").

## Pull

```bash
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php get {name} --output projects/{slug}/bpmn/{name}.xml
```

Order: upsert-script-php → upsert-screen-yaml → this skill.
