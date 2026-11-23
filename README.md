# Processmaker Package Skeleton
This package provides the necessary base code to start the developing a package in ProcessMaker 4.

## Requirements

| Tool | Version | Used for |
| --- | --- | --- |
| PHP | >= 8.4 with `curl`, `json`, `mbstring`, `tokenizer`, `xmlwriter`, `simplexml` | the setup wizard, `upsert-*` deploy scripts, code style tools |
| Composer | 2.x | `create-project`, `composer cs` |
| Node.js + npm | >= 22 (24 recommended, see `.nvmrc`) | `npx skills add` (during setup), Vite, Storybook |
| Git | any recent | pushing the plugin; ProcessMaker installs plugins from a git URL |

Node.js 20 is end-of-life and no longer receives security fixes, so it is rejected. Keep Node.js on the latest patch of its line: security fixes ship as patch releases. The check below prints a warning (it never fails) when a newer patch of your Node.js line exists; set `PM_SKIP_NODE_PATCH_CHECK=1` to skip that lookup (it is also skipped silently when nodejs.org is unreachable).

Check them with `composer doctor` (or `composer run-script doctor` / `php setup/requirements.php`; the last also supports `--json`). It exits with a non-zero code when something is missing.

Tests run inside ProcessMaker core, not standalone: see "PHPUnit development" in [AGENTS.md](AGENTS.md).

## Create project (recommended)

```bash
composer create-project --stability=dev processmaker/package-skeleton:dev-feature/FOUR-34328 my-package
```

The post-install wizard checks the requirements (it stops if Node.js is missing), asks for **Migration** or **PM4 only**, then **ProcessMaker URL**, **API token** (optional), writes the MCP config for Claude Code (`.mcp.json`), Cursor (`.cursor/mcp.json`) and VS Code (`.vscode/mcp.json`) with the 4 ProcessMaker MCP servers (processes, screens, data-connectors, flow-genies), writes `.env.example`, and installs skills. Authorize the MCP servers with OAuth in your IDE. For Codex, the wizard prints the `codex mcp add` / `codex mcp login` commands to run.

Asset publish (PM4 core deploy APIs; set `PM_BASE_URL` / `PM_SYNC_TOKEN`), in order:

```bash
php .agents/skills/upsert-script-php/scripts/upsert-script.php projects/{slug}/scripts/my-script.php
php .agents/skills/upsert-screen-yaml/scripts/upsert-screen.php projects/{slug}/screens/my-screen.yaml
php .agents/skills/upsert-process-bpmn/scripts/sync-process.php upsert projects/{slug}/bpmn/my-process.xml
```

### Non-interactive

```bash
PM_SKILLS_PROFILE=migration PM_BASE_URL=https://pm.example.com \
PM_PLUGIN_REPO=https://github.com/my-org/my-package.git PM_PLUGIN_BRANCH=main \
  composer create-project --no-interaction --stability=dev processmaker/package-skeleton:dev-feature/FOUR-34328 my-package
```

| Variable | Meaning |
| --- | --- |
| `PM_SKILLS_PROFILE` | `pm4` (PM4 skills) or `migration` (PM3 + PM4 + migration skills). Without it no skills are installed. |
| `PM_BASE_URL` | ProcessMaker URL used for the MCP config and `.env`. `NAYRA_SERVER` is still accepted as an alias. |
| `PM_SYNC_TOKEN` | ProcessMaker API token for the `upsert-*` deploy scripts. Written to `.env` (git-ignored). It is not used for MCP, which uses OAuth. |
| `PM_PLUGIN_REPO` | `https://` URL of the plugin's git repository. The repository name **must equal the package name** (the wizard stops otherwise): ProcessMaker installs the plugin as `storage/plugins/{repo-name}` and runs `{repo-name}:install`. The wizard then runs `git init`, sets the branch and adds `origin`. |
| `PM_PLUGIN_BRANCH` | Branch to use with `PM_PLUGIN_REPO`. Defaults to `main`. |

## Install the plugin in ProcessMaker

ProcessMaker installs a plugin with `git clone`, `composer install` and `{package-name}:install` (which publishes the assets). It **never runs `npm`**, so build and commit the assets first:

```bash
npm install
npm run prod        # builds public/
git add -A && git commit -m "Initial plugin" && git push -u origin main
```

Then open **Admin › Plugins**, click **Plugin**, enter the repository URL (and username/token for a private repository), and click **Save** (ProcessMaker then installs the plugin). To update later, push your changes and use the plugin's update action in the same page.

The repository must contain the plugin (a `composer.json` at its root): installing an empty repository fails.

### Running ProcessMaker in the Docker dev stack

If you run core with the `pm4_core` container (`./processmaker` is mounted at `/core`), you can scaffold directly into core's plugins folder without installing PHP, Composer or Node on your machine:

```bash
docker exec -it pm4_core composer create-project --stability=dev \
  processmaker/package-skeleton:dev-feature/FOUR-34328 storage/plugins/my-package
```

## Development
Prefer `composer create-project` above. To rename a local clone:

```
git clone https://github.com/ProcessMaker/package-skeleton.git my-package
cd my-package
php rename-project.php my-package
composer install
npm install
npm run prod
```

Storybook runs on port 7007 (`npm run storybook`, `npm run test-storybook`).

For Screen Builder screens (YAML + `VueComponentRenderTemplate`), place Vue SFCs under `resources/js/projects/{camelSlug}/{screenFolder}/` (shared pieces in `resources/js/projects/{camelSlug}/shared/` or plugin-wide `resources/js/projects/shared/`), register them in `resources/js/designer/public-screen-components.json`, then `npm run prod` and publish assets on ProcessMaker. See §4 of [AGENTS.md](AGENTS.md) and [ai-docs/screen-builder.md](ai-docs/screen-builder.md).

## Navigation and testing
* Navigate to administration tab in your ProcessMaker 4
* Select `Skeleton Package` from the administrative sidebar

## Uninstall
* Delete the plugin in **Admin › Plugins**, or run `php artisan package-skeleton:uninstall`
* Use `composer remove processmaker/package-skeleton` to remove the package completely
