This folder contains the structure of each project. Each of them is organized as follows:

- `projects/{project-name}/bpmn/` — BPMN processes
- `projects/{project-name}/screens/` — YAML screens
- `projects/{project-name}/scripts/` — PHP scripts

See [AGENTS.md](./AGENTS.md) and root [AGENTS.md](../AGENTS.md).

## Notation rules

- Assets use kebab-case names, e.g. `project-name-intake.xml`, `project-name-form.yaml`, `project-name-save.php`
- Basename of script/screen files equals the ProcessMaker asset title
