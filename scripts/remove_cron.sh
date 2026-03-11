#!/bin/bash

set -euo pipefail

MATCH_TEXT="${MATCH_TEXT:-simplemdm_sync.py}"

TMP_FILE="$(mktemp)"
trap 'rm -f "$TMP_FILE"' EXIT

crontab -l 2>/dev/null | grep -v "$MATCH_TEXT" > "$TMP_FILE" || true
crontab "$TMP_FILE"

echo "Removed cron entries matching: $MATCH_TEXT"
