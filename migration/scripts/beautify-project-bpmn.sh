#!/usr/bin/env bash
# Apply pm4-bpmn-beautify (task-type colors + layout) to a project BPMN file.
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 path/to/projects/{slug}/bpmn/{name}.xml" >&2
  exit 1
fi

BPMN="$1"
if [[ ! -f "$BPMN" ]]; then
  echo "File not found: $BPMN" >&2
  exit 1
fi

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CLI="${PM4_BPMN_BEAUTIFY_CLI:-}"

if [[ -z "$CLI" ]]; then
  for candidate in \
    "$ROOT/../skills/skills/pm4-bpmn-beautify/scripts/cli.mjs" \
    "$ROOT/../../components/skills/skills/pm4-bpmn-beautify/scripts/cli.mjs"; do
    if [[ -f "$candidate" ]]; then
      CLI="$candidate"
      break
    fi
  done
fi

if [[ -z "$CLI" || ! -f "$CLI" ]]; then
  echo "Set PM4_BPMN_BEAUTIFY_CLI to skills/pm4-bpmn-beautify/scripts/cli.mjs" >&2
  exit 1
fi

TMP="$(mktemp "${TMPDIR:-/tmp}/beautify-XXXXXX.bpmn")"
node "$CLI" beautify "$BPMN" --output "$TMP" --theme professional
mv "$TMP" "$BPMN"
echo "Beautified $BPMN"