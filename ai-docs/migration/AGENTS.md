# PM3 → PM4 migration (package wizard option 1)

Target PM4 instance is configured during `composer create-project` (same MCP + `.env` as PM4-only).

## Inputs

Place PM3 exports under `migration/inputs/` (`.pmx`, `.pmx2`, supporting files).

## Flow

1. Run `pm-analyze` (installed skill) → report under `migration/outputs/`.
2. Generate assets under `projects/{slug}/` per root [AGENTS.md](../../AGENTS.md).
3. Publish: MCP (`/mcp/screens`, `/mcp/processes`) or package deploy API when available.
4. Coverage report: [ai-docs/report.md](../report.md).
