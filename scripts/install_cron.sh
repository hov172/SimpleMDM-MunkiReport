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
# Secrets live in a 0600 file that the cron job sources, never on the cron
# command line: a crontab entry is readable via `ps` while the job runs and,
# on many systems, from the spool directory.
ENV_FILE="${ENV_FILE:-$HOME/.simplemdm_sync.env}"

# Single-quote a value for safe re-sourcing, handling embedded single quotes.
shell_quote() {
    printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\\\''/g")"
}

usage() {
    cat <<EOF
Usage:
  $(basename "$0") --munkireport-url URL --api-key KEY [--install]
  $(basename "$0") --remove [--match-text TEXT]

Options:
  --munkireport-url URL   Required. Base MunkiReport URL used by the sync script.
  --api-key KEY           Required for host/manual runner installs. Written to
                          the 0600 env file below, never to the crontab line.
                          Prefer exporting SIMPLEMDM_API_KEY instead, so the key
                          does not appear in your shell history.
  --munkireport-token TOKEN
                          Optional MunkiReport API token. Handled like --api-key.
  --env-file PATH         Where secrets are stored for the cron job to source.
                          Must not be inside the web root. Default: $ENV_FILE
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
  MUNKIREPORT_URL, SIMPLEMDM_API_KEY, MUNKIREPORT_TOKEN, PYTHON_BIN, SCHEDULE, LOG_PATH, MAX_PARENT_RESOURCES, INSTALL_CRON, REMOVE_CRON, MATCH_TEXT, ENV_FILE
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
        --env-file)
            ENV_FILE="${2:-}"
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

case "$ENV_FILE" in
    /*) ;;
    *)
        echo "ERROR: --env-file must be an absolute path (got '$ENV_FILE')." >&2
        exit 1
        ;;
esac

# Canonicalise before the containment check. A textual prefix match is
# bypassed by '..' or a symlinked parent, which would put the secrets file
# under the web root while appearing to pass the check.
ENV_FILE_DIR=$(cd "$(dirname "$ENV_FILE")" 2>/dev/null && pwd -P) || true
if [ -z "$ENV_FILE_DIR" ]; then
    echo "ERROR: the directory for --env-file does not exist: $(dirname "$ENV_FILE")" >&2
    exit 1
fi
ENV_FILE="$ENV_FILE_DIR/$(basename "$ENV_FILE")"

# A symlink target is resolved by '>' on write, so it would sidestep the
# check below entirely. Refuse rather than chase it.
if [ -L "$ENV_FILE" ]; then
    echo "ERROR: --env-file must not be a symlink (got '$ENV_FILE')." >&2
    exit 1
fi

MODULE_DIR_REAL=$(cd "$MODULE_DIR" && pwd -P)
case "$ENV_FILE" in
    "$MODULE_DIR_REAL"|"$MODULE_DIR_REAL"/*)
        echo "ERROR: --env-file must not live inside the module directory; it would be web-served." >&2
        echo "       Resolved to: $ENV_FILE" >&2
        exit 1
        ;;
esac

# The cron job sources the env file, so no secret appears in the crontab line.
CRON_CMD="set -a; . '$ENV_FILE'; set +a; $PYTHON_BIN $SYNC_SCRIPT --munkireport-url '$MUNKIREPORT_URL' --respect-schedule --max-parent-resources $MAX_PARENT_RESOURCES"
CRON_CMD="$CRON_CMD >> $LOG_PATH 2>&1"
CRON_LINE="$SCHEDULE $CRON_CMD"

echo "MunkiReport root: $MR_ROOT"
echo "Module path: $MODULE_DIR"
echo "Sync script: $SYNC_SCRIPT"
echo "Secrets file: $ENV_FILE (mode 0600, holds SIMPLEMDM_API_KEY)"
echo ""
echo "Cron entry:"
echo "$CRON_LINE"

if [ "$INSTALL_CRON" != "1" ]; then
    echo ""
    echo "Printed only. Re-run with --install to write $ENV_FILE and update the current user's crontab."
    exit 0
fi

# Write the secrets file first, with a restrictive umask so it is never
# briefly world-readable.
(
    umask 077
    {
        echo "# Written by install_cron.sh. Keep mode 0600."
        echo "SIMPLEMDM_API_KEY=$(shell_quote "$SIMPLEMDM_API_KEY")"
        if [ -n "$MUNKIREPORT_TOKEN" ]; then
            echo "MUNKIREPORT_TOKEN=$(shell_quote "$MUNKIREPORT_TOKEN")"
        fi
    } > "$ENV_FILE"
)
chmod 600 "$ENV_FILE"
echo "Wrote $ENV_FILE (mode 0600)."

TMP_FILE="$(mktemp)"
trap 'rm -f "$TMP_FILE"' EXIT

crontab -l 2>/dev/null | grep -v "simplemdm_sync.py" > "$TMP_FILE" || true
printf "%s\n" "$CRON_LINE" >> "$TMP_FILE"
crontab "$TMP_FILE"

echo ""
echo "Installed cron entry for the current user."
