#!/usr/bin/env bash
# .codex/hooks/turn-complete.sh
# Fires on agent-turn-complete — logs turn, checks for rationalization phrases.
#
# IMPORTANT LIMITATION vs Claude Code:
# Claude Code's anti-rationalization hook uses the Stop event to BLOCK Codex
# from stopping prematurely. This hook fires AFTER the turn completes and
# CANNOT block. It can only log a warning to a file you review manually.
#
# Full Stop-hook behavior requires Codex to support a PreStop/Stop event
# (not yet available as of 2026).

set -euo pipefail

LOG_DIR=".codex/logs"
mkdir -p "$LOG_DIR"

TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
LOG_FILE="$LOG_DIR/turns.log"

# Read the turn data from stdin (Codex passes JSON)
TURN_DATA=$(cat 2>/dev/null || echo "{}")

# Extract last assistant message if available
LAST_MSG=$(echo "$TURN_DATA" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    msg = data.get('last-assistant-message', '')
    print(msg[:500])
except:
    print('')
" 2>/dev/null || echo "")

# Rationalization phrases that indicate premature stopping
RATIONALIZATION_PATTERNS=(
  "pre-existing"
  "out of scope"
  "follow-up pr"
  "follow-up ticket"
  "future improvement"
  "not part of this task"
  "leave that for later"
  "too many issues"
)

FLAGGED=0
for pattern in "${RATIONALIZATION_PATTERNS[@]}"; do
  if echo "$LAST_MSG" | grep -qi "$pattern"; then
    FLAGGED=1
    echo "[$TIMESTAMP] ⚠️  RATIONALIZATION DETECTED: '$pattern' found in turn output" >> "$LOG_FILE"
    echo "[$TIMESTAMP]    Message preview: ${LAST_MSG:0:200}" >> "$LOG_FILE"
  fi
done

# Log the turn
echo "[$TIMESTAMP] Turn complete | flagged=$FLAGGED" >> "$LOG_FILE"

# If flagged, also write a prominent warning
if [ "$FLAGGED" -eq 1 ]; then
  cat >> "$LOG_FILE" <<'EOF'
    ↑ Codex may have stopped prematurely. Review the response and continue
      if work is not actually complete. Claude Code's Stop hook would have
      blocked this — here you must intervene manually.
EOF

  # macOS desktop notification if available
  if command -v osascript &>/dev/null; then
    osascript -e 'display notification "Codex may have stopped prematurely — check .codex/logs/turns.log" with title "⚠️ Rationalization Detected"' 2>/dev/null || true
  fi
fi

exit 0
