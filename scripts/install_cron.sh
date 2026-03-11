#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DIR="$(dirname "$SCRIPT_DIR")"
MR_ROOT="$(cd "$MODULE_DIR/../../.." && pwd)"
SYNC_SCRIPT="$SCRIPT_DIR/simplemdm_sync.py"

MUNKIREPORT_URL="${MUNKIREPORT_URL:-}"
SIMPLEMDM_API_KEY="${SIMPLEMDM_API_KEY:-}"
MUNKIREPORT_TOKEN="${MUNKIREPORT_TOKEN:-}"
PYTHON_BIN="${PYTHON_BIN:-/usr/bin/python3}"
SCHEDULE="${SCHEDULE:-* * * * *}"
MAX_PARENT_RESOURCES="${MAX_PARENT_RESOURCES:-25}"
LOG_PATH="${LOG_PATH:-/var/log/simplemdm_sync.log}"
INSTALL_CRON="${INSTALL_CRON:-0}"
REMOVE_CRON="${REMOVE_CRON:-0}"
MATCH_TEXT="${MATCH_TEXT:-simplemdm_sync.py}"

usage() {
    cat <<EOF
Usage:
  $(basename "$0") --munkireport-url URL --api-key KEY [--install]
  $(basename "$0") --remove [--match-text TEXT]

Options:
  --munkireport-url URL   Required. Base MunkiReport URL used by the sync script.
  --api-key KEY           Required for host/manual runner installs. Passed to simplemdm_sync.py.
  --munkireport-token TOKEN
                          Optional MunkiReport API token passed to simplemdm_sync.py.
  --python-bin PATH       Python binary to use. Default: $PYTHON_BIN
  --schedule SPEC         Cron schedule. Default: "$SCHEDULE"
  --log-path PATH         Log file path. Default: $LOG_PATH
  --max-parent-resources N
                          Passed to simplemdm_sync.py. Default: $MAX_PARENT_RESOURCES
  --install               Install/update the current user's crontab entry.
  --remove                Remove matching cron entries instead of printing/installing.
  --match-text TEXT       Match text used by --remove. Default: $MATCH_TEXT
  --print-only            Print the cron entry without installing it. Default behavior.
  -h, --help              Show this help.

Environment overrides:
  MUNKIREPORT_URL, SIMPLEMDM_API_KEY, MUNKIREPORT_TOKEN, PYTHON_BIN, SCHEDULE, LOG_PATH, MAX_PARENT_RESOURCES, INSTALL_CRON, REMOVE_CRON, MATCH_TEXT
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --munkireport-url)
            MUNKIREPORT_URL="${2:-}"
            shift 2
            ;;
        --python-bin)
            PYTHON_BIN="${2:-}"
            shift 2
            ;;
        --api-key)
            SIMPLEMDM_API_KEY="${2:-}"
            shift 2
            ;;
        --munkireport-token)
            MUNKIREPORT_TOKEN="${2:-}"
            shift 2
            ;;
        --schedule)
            SCHEDULE="${2:-}"
            shift 2
            ;;
        --log-path)
            LOG_PATH="${2:-}"
            shift 2
            ;;
        --max-parent-resources)
            MAX_PARENT_RESOURCES="${2:-}"
            shift 2
            ;;
        --install)
            INSTALL_CRON=1
            shift
            ;;
        --remove)
            REMOVE_CRON=1
            shift
            ;;
        --match-text)
            MATCH_TEXT="${2:-}"
            shift 2
            ;;
        --print-only)
            INSTALL_CRON=0
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "ERROR: Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

if [ "$REMOVE_CRON" = "1" ]; then
    TMP_FILE="$(mktemp)"
    trap 'rm -f "$TMP_FILE"' EXIT
    crontab -l 2>/dev/null | grep -v "$MATCH_TEXT" > "$TMP_FILE" || true
    crontab "$TMP_FILE"
    echo "Removed cron entries matching: $MATCH_TEXT"
    exit 0
fi

if [ -z "$MUNKIREPORT_URL" ]; then
    echo "ERROR: --munkireport-url is required." >&2
    usage >&2
    exit 1
fi

if [ -z "$SIMPLEMDM_API_KEY" ]; then
    echo "ERROR: --api-key is required for host/manual runner installs." >&2
    echo "The cron worker should not rely on an authenticated MunkiReport session to discover the API key." >&2
    usage >&2
    exit 1
fi

if [ ! -f "$SYNC_SCRIPT" ]; then
    echo "ERROR: Sync script not found at $SYNC_SCRIPT" >&2
    exit 1
fi

CRON_CMD="$PYTHON_BIN $SYNC_SCRIPT --api-key '$SIMPLEMDM_API_KEY' --munkireport-url '$MUNKIREPORT_URL' --respect-schedule --max-parent-resources $MAX_PARENT_RESOURCES"
if [ -n "$MUNKIREPORT_TOKEN" ]; then
    CRON_CMD="$CRON_CMD --munkireport-token '$MUNKIREPORT_TOKEN'"
fi
CRON_CMD="$CRON_CMD >> $LOG_PATH 2>&1"
CRON_LINE="$SCHEDULE $CRON_CMD"

echo "MunkiReport root: $MR_ROOT"
echo "Module path: $MODULE_DIR"
echo "Sync script: $SYNC_SCRIPT"
echo ""
echo "Cron entry:"
echo "$CRON_LINE"

if [ "$INSTALL_CRON" != "1" ]; then
    echo ""
    echo "Printed only. Re-run with --install to update the current user's crontab."
    exit 0
fi

TMP_FILE="$(mktemp)"
trap 'rm -f "$TMP_FILE"' EXIT

crontab -l 2>/dev/null | grep -v "simplemdm_sync.py" > "$TMP_FILE" || true
printf "%s\n" "$CRON_LINE" >> "$TMP_FILE"
crontab "$TMP_FILE"

echo ""
echo "Installed cron entry for the current user."
