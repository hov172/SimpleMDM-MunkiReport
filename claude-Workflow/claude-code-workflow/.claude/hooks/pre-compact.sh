#!/bin/bash
# PreCompact hook — saves critical context before compaction.
# Compaction can destroy nuanced understanding built up during the session.
# This hook creates a handoff file with the most important context to preserve.
#
# The handoff is written to .claude/handoffs/ and referenced in the next session.

# Requires jq — fail open if missing
if ! command -v jq >/dev/null 2>&1; then
  exit 0
fi

INPUT=$(cat)
TRIGGER=$(echo "$INPUT" | jq -r '.trigger // "auto"')
CUSTOM=$(echo "$INPUT" | jq -r '.custom_instructions // ""')

# Create handoffs directory
mkdir -p "$CLAUDE_PROJECT_DIR/.claude/handoffs" 2>/dev/null || \
mkdir -p ".claude/handoffs" 2>/dev/null

HANDOFF_DIR="${CLAUDE_PROJECT_DIR:-.}/.claude/handoffs"
HANDOFF_FILE="$HANDOFF_DIR/$(date +%Y-%m-%dT%H%M%S)-pre-compact.md"

{
  echo "---"
  echo "date: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "trigger: $TRIGGER"
  echo "---"
  echo ""
  echo "# Pre-Compact Handoff"
  echo ""
  echo "## Git State"
  echo "- Branch: $(git branch --show-current 2>/dev/null || echo 'unknown')"
  echo "- Last commit: $(git log --oneline -1 2>/dev/null || echo 'unknown')"
  echo ""
  echo "## Modified Files"
  git status --porcelain 2>/dev/null | head -30 || echo "No git status available"
  echo ""
  echo "## Staged Files"
  git diff --cached --name-only 2>/dev/null | head -20 || echo "None"
  echo ""
  if [ -n "$CUSTOM" ]; then
    echo "## Custom Preserve Instructions"
    echo "$CUSTOM"
    echo ""
  fi
  echo "## Active tasks/todo.md"
  if [ -f "${CLAUDE_PROJECT_DIR:-.}/tasks/todo.md" ]; then
    head -50 "${CLAUDE_PROJECT_DIR:-.}/tasks/todo.md"
  else
    echo "No tasks/todo.md found"
  fi
} > "$HANDOFF_FILE" 2>/dev/null

# Output context for Claude to see after compaction
echo "Pre-compact handoff saved to: $HANDOFF_FILE"
echo "Branch: $(git branch --show-current 2>/dev/null)"
echo "Modified files: $(git status --porcelain 2>/dev/null | wc -l | tr -d ' ') files"

exit 0
