#!/usr/bin/env bash
# .claude/hooks/session-start.sh
# Fires on SessionStart — injects git context so every session starts oriented.
# 
# Hook Profile (inspired by ECC): set ECC_HOOK_PROFILE env var to control verbosity
#   minimal  — branch + last commit only
#   standard — branch, commit, file counts, staged, stash (DEFAULT)
#   strict   — all of the above + active PR info
#
# Disable this hook: add "session-start" to ECC_DISABLED_HOOKS env var
# Example: export ECC_HOOK_PROFILE=strict
#          export ECC_DISABLED_HOOKS="session-start,warn-large-files"

set -euo pipefail

PROFILE="${ECC_HOOK_PROFILE:-standard}"

# Check if this hook is disabled
if [[ "${ECC_DISABLED_HOOKS:-}" == *"session-start"* ]]; then
  exit 0
fi

# Must be in a git repo
if ! git rev-parse --git-dir > /dev/null 2>&1; then
  exit 0
fi

BRANCH=$(git branch --show-current 2>/dev/null || git rev-parse --short HEAD 2>/dev/null || echo "unknown")
LAST_COMMIT=$(git log -1 --pretty=format:"%s" 2>/dev/null || echo "no commits")

if [[ "$PROFILE" == "minimal" ]]; then
  echo "=== SESSION START === branch: $BRANCH | last commit: $LAST_COMMIT"
  exit 0
fi

MODIFIED=$(git status --porcelain 2>/dev/null | wc -l | tr -d ' ')
STAGED=$(git diff --cached --name-only 2>/dev/null | wc -l | tr -d ' ')
STASHES=$(git stash list 2>/dev/null | wc -l | tr -d ' ')

echo "=== SESSION START ==="
echo "Branch: $BRANCH"
echo "Last commit: $LAST_COMMIT"
echo "Uncommitted: $MODIFIED files | Staged: $STAGED | Stashes: $STASHES"

# strict: also fetch active PR info
if [[ "$PROFILE" == "strict" ]] && command -v gh &>/dev/null; then
  PR_INFO=$(gh pr view --json number,title,state --jq '"PR #\(.number): \(.title) [\(.state)]"' 2>/dev/null || echo "")
  [[ -n "$PR_INFO" ]] && echo "Active PR: $PR_INFO"
fi

echo "========================"
