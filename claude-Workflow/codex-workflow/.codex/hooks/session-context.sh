#!/usr/bin/env bash
# .codex/hooks/session-context.sh
# Fires on userpromptsubmit — injects git context into the prompt.
# Codex equivalent of Claude Code's session-start.sh SessionStart hook.
#
# NOTE: Unlike Claude Code's SessionStart, this fires on EVERY prompt submission,
# not just at session start. Keep it fast (< 2 seconds).

set -euo pipefail

# Only inject context if we're in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
  exit 0
fi

BRANCH=$(git branch --show-current 2>/dev/null || git rev-parse --short HEAD)
LAST_COMMIT=$(git log -1 --pretty=format:"%s" 2>/dev/null || echo "no commits")
MODIFIED=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
STAGED=$(git diff --cached --name-only 2>/dev/null | wc -l | tr -d ' ')
STASHES=$(git stash list 2>/dev/null | wc -l | tr -d ' ')

# Check for active PR (requires gh CLI)
PR_INFO=""
if command -v gh &>/dev/null; then
  PR_INFO=$(gh pr view --json number,title --jq '"PR #\(.number): \(.title)"' 2>/dev/null || echo "")
fi

# Output context block — Codex will prepend this to the user's prompt
cat <<EOF
[Git context: branch=$BRANCH | last commit="$LAST_COMMIT" | modified=$MODIFIED files | staged=$STAGED | stashes=$STASHES${PR_INFO:+ | $PR_INFO}]
EOF
