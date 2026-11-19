# PM3 → PM4 migration (package wizard option 1)

Target PM4 instance is configured during `composer create-project` (same MCP + `.env` as PM4-only).

## Inputs

Place PM3 exports under `migration/inputs/` (`.pmx`, `.pmx2`, supporting files).

## Flow

1. Run `pm-analyze` (installed skill) → report under `migration/outputs/`.
2. Generate assets under `projects/{slug}/` per root [AGENTS.md](../../AGENTS.md). Screen Vue components go in this package under `resources/js/projects/{camelSlug}/` (root AGENTS.md §4.1–4.2), never in `package-plg`.
3. Publish (PM4 core deploy APIs): script → screen → process:
   - `upsert-script-php/scripts/upsert-script.php`
   - `upsert-screen-yaml/scripts/upsert-screen.php`
   - `upsert-process-bpmn/scripts/sync-process.php upsert …`
4. Coverage report: [ai-docs/report.md](../report.md).
