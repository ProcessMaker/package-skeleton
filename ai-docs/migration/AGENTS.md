# PM3 → PM4 migration (package wizard option 1)

Target PM4 instance is configured during `composer create-project` (same MCP + `.env` as PM4-only).

## Inputs

Place PM3 exports under `migration/inputs/` (`.pmx`, `.pmx2`, supporting files).

## Flow

1. Run `pm-analyze` (installed skill) → report under `migration/outputs/`.
2. Generate assets under `projects/{slug}/` per root [AGENTS.md](../../AGENTS.md). Screen Vue components go in this package under `resources/js/projects/{camelSlug}/` (root AGENTS.md §4.1–4.2), never in `package-plg`.
3. Beautify BPMN: `migration/scripts/beautify-project-bpmn.sh projects/{slug}/bpmn/{name}.xml` (or `PM4_BPMN_BEAUTIFY_CLI` → [pm4-bpmn-beautify](https://github.com/ProcessMaker/skills/tree/main/skills/pm4-bpmn-beautify)) before publish.
4. Publish (PM4 core deploy APIs): script → screen → process (metadata sidecars: `projects/{slug}/process-description.txt`, `process-category.txt`; per script `*.description.txt` / `*.category.txt`; screen YAML `description` + `category`):
   - `upsert-script-php/scripts/upsert-script.php`
   - `upsert-screen-yaml/scripts/upsert-screen.php`
   - `upsert-process-bpmn/scripts/sync-process.php upsert …`
5. Coverage report: [ai-docs/report.md](../report.md).
