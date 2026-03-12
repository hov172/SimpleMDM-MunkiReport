#!/bin/sh
set -eu

# Example Option B client reporter for macOS.
#
# Required environment variables:
#   MUNKIREPORT_URL
#   SIMPLEMDM_CLIENT_SECRET
#
# Optional environment variables:
#   SIMPLEMDM_CLIENT_SERIAL
#   SIMPLEMDM_CLIENT_SOURCE
#   SIMPLEMDM_CLIENT_VERSION
#   CURL_BIN
#
# This example uses the original shared-secret Option B flow.

CURL_BIN="${CURL_BIN:-/usr/bin/curl}"
MUNKIREPORT_URL="${MUNKIREPORT_URL:-}"
SIMPLEMDM_CLIENT_SECRET="${SIMPLEMDM_CLIENT_SECRET:-}"
SIMPLEMDM_CLIENT_SERIAL="${SIMPLEMDM_CLIENT_SERIAL:-}"
SIMPLEMDM_CLIENT_SOURCE="${SIMPLEMDM_CLIENT_SOURCE:-client_reporter}"
SIMPLEMDM_CLIENT_VERSION="${SIMPLEMDM_CLIENT_VERSION:-1.0.0}"

if [ -z "$MUNKIREPORT_URL" ]; then
    echo "ERROR: MUNKIREPORT_URL is required" >&2
    exit 1
fi

if [ -z "$SIMPLEMDM_CLIENT_SECRET" ]; then
    echo "ERROR: SIMPLEMDM_CLIENT_SECRET is required" >&2
    exit 1
fi

if [ -z "$SIMPLEMDM_CLIENT_SERIAL" ]; then
    SIMPLEMDM_CLIENT_SERIAL="$(/usr/sbin/system_profiler SPHardwareDataType 2>/dev/null | /usr/bin/awk -F': ' '/Serial Number/ {print $2; exit}')"
fi

if [ -z "$SIMPLEMDM_CLIENT_SERIAL" ]; then
    echo "ERROR: Unable to determine serial number" >&2
    exit 1
fi

CONSOLE_USER="$(/usr/bin/stat -f %Su /dev/console 2>/dev/null || true)"

UPTIME_SECONDS="$(
    /usr/bin/python3 - <<'PY'
import subprocess
import time

try:
    out = subprocess.check_output(["/usr/sbin/sysctl", "-n", "kern.boottime"], text=True)
    sec = int(out.split("sec = ")[1].split(",")[0])
    print(int(time.time()) - sec)
except Exception:
    print(0)
PY
)"

LOCAL_FILEVAULT_ENABLED=0
if /usr/bin/fdesetup status 2>/dev/null | /usr/bin/grep -qi 'FileVault is On'; then
    LOCAL_FILEVAULT_ENABLED=1
fi

PAYLOAD="$(
    SIMPLEMDM_CLIENT_SERIAL="$SIMPLEMDM_CLIENT_SERIAL" \
    SIMPLEMDM_CLIENT_SOURCE="$SIMPLEMDM_CLIENT_SOURCE" \
    SIMPLEMDM_CLIENT_VERSION="$SIMPLEMDM_CLIENT_VERSION" \
    CONSOLE_USER="$CONSOLE_USER" \
    UPTIME_SECONDS="$UPTIME_SECONDS" \
    LOCAL_FILEVAULT_ENABLED="$LOCAL_FILEVAULT_ENABLED" \
    /usr/bin/python3 - <<'PY'
import json
import os

payload = {
    "serial_number": os.environ["SIMPLEMDM_CLIENT_SERIAL"],
    "source": os.environ["SIMPLEMDM_CLIENT_SOURCE"],
    "client_version": os.environ["SIMPLEMDM_CLIENT_VERSION"],
    "facts": {
        "console_user": os.environ.get("CONSOLE_USER", ""),
        "uptime_seconds": int(os.environ.get("UPTIME_SECONDS", "0") or "0"),
        "local_filevault_enabled": os.environ.get("LOCAL_FILEVAULT_ENABLED", "0") == "1",
    },
}
print(json.dumps(payload, separators=(",", ":")))
PY
)"

exec "$CURL_BIN" -fsS \
    -X POST "${MUNKIREPORT_URL%/}/index.php?/module/simplemdm/index?op=ingest_client_facts" \
    -H "Content-Type: application/json" \
    -H "X-SIMPLEMDM-CLIENT-SECRET: ${SIMPLEMDM_CLIENT_SECRET}" \
    -d "$PAYLOAD"
