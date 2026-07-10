#!/bin/bash
# Anti-rationalization gate — Stop hook.
# Reviews Claude's final response before allowing it to stop.
# Catches Claude declaring victory while rationalizing incomplete work.
# Uses Haiku (fast, cheap) to evaluate the response.
#
# Patterns caught:
#   - "these issues were pre-existing"
#   - "fixing this is out of scope"
#   - "I'll leave these for a follow-up"
#   - listing problems without fixing them
#   - skipping test/lint failures with excuses
#
# Exit 2 = block and force Claude to continue.
# Exit 0 = allow Claude to stop.
#
# Source: Trail of Bits (trailofbits/claude-code-config)

# Requires jq — fail open if missing (don't block the user)
if ! command -v jq >/dev/null 2>&1; then
  exit 0
fi

# Requires ANTHROPIC_API_KEY
if [ -z "$ANTHROPIC_API_KEY" ]; then
  exit 0
fi

INPUT=$(cat)
RESPONSE=$(echo "$INPUT" | jq -r '.assistant_response // empty' 2>/dev/null)

if [ -z "$RESPONSE" ]; then
  exit 0
fi

# Quick local check for obvious rationalization phrases before calling the API
RATIONALIZATION_PATTERNS=(
  "pre-existing"
  "out of scope"
  "leave.*follow-up"
  "too many issues"
  "address.*later"
  "separate PR"
  "not part of"
  "beyond the scope"
  "already existing"
  "were already there"
)

QUICK_MATCH=false
for pattern in "${RATIONALIZATION_PATTERNS[@]}"; do
  if echo "$RESPONSE" | grep -qiE "$pattern"; then
    QUICK_MATCH=true
    break
  fi
done

# If no quick match, allow — don't waste API calls on clean responses
if [ "$QUICK_MATCH" = false ]; then
  exit 0
fi

# Call Haiku to evaluate the response
EVALUATION=$(curl -s -X POST "https://api.anthropic.com/v1/messages" \
  -H "Content-Type: application/json" \
  -H "x-api-key: $ANTHROPIC_API_KEY" \
  -H "anthropic-version: 2023-06-01" \
  -d "{
    \"model\": \"claude-haiku-4-5-20251001\",
    \"max_tokens\": 200,
    \"system\": \"You are a JSON-only evaluator. Respond with ONLY a raw JSON object — no markdown, no code fences, no explanation.\",
    \"messages\": [{
      \"role\": \"user\",
      \"content\": \"Review this assistant response. Reject (ok: false) if the assistant is rationalizing incomplete work by: claiming issues are pre-existing or out of scope, deferring to unrequested follow-ups, listing problems without fixing them, skipping test/lint failures with excuses, or declaring victory before work is verifiably done. If clean, approve (ok: true).\n\nResponse to evaluate:\n$(echo "$RESPONSE" | head -c 2000 | sed 's/"/\\"/g' | tr '\n' ' ')\n\nRespond ONLY with: {\\\"ok\\\": true} or {\\\"ok\\\": false, \\\"reason\\\": \\\"brief reason\\\"}\"
    }]
  }" 2>/dev/null)

OK=$(echo "$EVALUATION" | jq -r '.content[0].text' 2>/dev/null | jq -r '.ok' 2>/dev/null)
REASON=$(echo "$EVALUATION" | jq -r '.content[0].text' 2>/dev/null | jq -r '.reason // "Rationalization detected — complete the work before stopping."' 2>/dev/null)

if [ "$OK" = "false" ]; then
  echo "{\"hookSpecificOutput\":{\"hookEventName\":\"Stop\",\"permissionDecision\":\"deny\",\"permissionDecisionReason\":\"Anti-rationalization gate: $REASON\"}}"
  exit 2
fi

exit 0
