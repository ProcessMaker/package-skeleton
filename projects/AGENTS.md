# Projects assets

Versioned ProcessMaker artifacts for each domain live here.

Layout (see [ai-docs/structure.md](../ai-docs/structure.md) and root [AGENTS.md](../AGENTS.md)):

```
projects/{slug}/
├── bpmn/       # {slug}-{purpose}.xml
├── scripts/    # {slug}-{action}.php (basename = script title)
└── screens/    # {slug}-{purpose}.yaml (basename = screen title)
```

Publish order: upsert-script-php → upsert-screen-yaml → upsert-process-bpmn.
