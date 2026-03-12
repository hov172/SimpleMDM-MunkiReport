#!/bin/bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

MODE="basic"
INSTALL_DIR="/usr/local/munki"
PLIST_PATH="/Library/LaunchDaemons/com.googlecode.munkireport-simplemdm-client-reporter.plist"
POSTFLIGHT_DIR="/usr/local/munki/postflight.d"
MUNKIREPORT_URL="${MUNKIREPORT_URL:-}"
SIMPLEMDM_CLIENT_SECRET="${SIMPLEMDM_CLIENT_SECRET:-}"
SIMPLEMDM_DEVICE_TOKEN="${SIMPLEMDM_DEVICE_TOKEN:-}"
CLIENT_VERSION="${SIMPLEMDM_CLIENT_VERSION:-1.0.0}"
SOURCE_NAME="${SIMPLEMDM_CLIENT_SOURCE:-client_reporter}"
START_INTERVAL="${START_INTERVAL:-900}"
INSTALL_LAUNCHD=0
INSTALL_POSTFLIGHT=0

usage() {
    cat <<EOF
Usage:
  sudo $(basename "$0") --munkireport-url URL --client-secret SECRET [options]

Options:
  --mode basic|hardened     Install the basic shell reporter or hardened Python reporter.
                            Default: $MODE
  --munkireport-url URL     Required. Base MunkiReport URL.
  --client-secret SECRET    Required. Value for X-SIMPLEMDM-CLIENT-SECRET.
  --device-token TOKEN      Required only for --mode hardened.
  --install-dir DIR         Destination directory for reporter files. Default: $INSTALL_DIR
  --install-launchd         Install a LaunchDaemon plist.
  --plist-path PATH         LaunchDaemon plist path. Default: $PLIST_PATH
  --start-interval SECONDS  LaunchDaemon StartInterval. Default: $START_INTERVAL
  --install-postflight      Install the example Munki postflight wrapper.
  --postflight-dir DIR      Destination directory for the postflight wrapper. Default: $POSTFLIGHT_DIR
  --client-version VALUE    Optional client version string. Default: $CLIENT_VERSION
  --source VALUE            Optional reporter source label. Default: $SOURCE_NAME
  -h, --help                Show this help.

Environment overrides:
  MUNKIREPORT_URL
  SIMPLEMDM_CLIENT_SECRET
  SIMPLEMDM_DEVICE_TOKEN
  SIMPLEMDM_CLIENT_VERSION
  SIMPLEMDM_CLIENT_SOURCE
  START_INTERVAL
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --mode)
            MODE="${2:-}"
            shift 2
            ;;
        --munkireport-url)
            MUNKIREPORT_URL="${2:-}"
            shift 2
            ;;
        --client-secret)
            SIMPLEMDM_CLIENT_SECRET="${2:-}"
            shift 2
            ;;
        --device-token)
            SIMPLEMDM_DEVICE_TOKEN="${2:-}"
            shift 2
            ;;
        --install-dir)
            INSTALL_DIR="${2:-}"
            shift 2
            ;;
        --install-launchd)
            INSTALL_LAUNCHD=1
            shift
            ;;
        --plist-path)
            PLIST_PATH="${2:-}"
            shift 2
            ;;
        --start-interval)
            START_INTERVAL="${2:-}"
            shift 2
            ;;
        --install-postflight)
            INSTALL_POSTFLIGHT=1
            shift
            ;;
        --postflight-dir)
            POSTFLIGHT_DIR="${2:-}"
            shift 2
            ;;
        --client-version)
            CLIENT_VERSION="${2:-}"
            shift 2
            ;;
        --source)
            SOURCE_NAME="${2:-}"
            shift 2
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

if [ -z "$MUNKIREPORT_URL" ]; then
    echo "ERROR: --munkireport-url is required." >&2
    exit 1
fi

if [ -z "$SIMPLEMDM_CLIENT_SECRET" ]; then
    echo "ERROR: --client-secret is required." >&2
    exit 1
fi

if [ "$MODE" = "hardened" ] && [ -z "$SIMPLEMDM_DEVICE_TOKEN" ]; then
    echo "ERROR: --device-token is required for --mode hardened." >&2
    exit 1
fi

mkdir -p "$INSTALL_DIR"

if [ "$MODE" = "basic" ]; then
    REPORTER_SRC="$SCRIPT_DIR/simplemdm_client_reporter_example.sh"
    REPORTER_DST="$INSTALL_DIR/simplemdm_client_reporter_example.sh"
    cp "$REPORTER_SRC" "$REPORTER_DST"
    chmod 755 "$REPORTER_DST"
else
    REPORTER_SRC="$SCRIPT_DIR/simplemdm_client_reporter_hardened.py"
    REPORTER_DST="$INSTALL_DIR/simplemdm_client_reporter_hardened.py"
    cp "$REPORTER_SRC" "$REPORTER_DST"
    chmod 755 "$REPORTER_DST"
fi

echo "Installed reporter:"
echo "  $REPORTER_DST"

if [ "$INSTALL_LAUNCHD" = "1" ]; then
    mkdir -p "$(dirname "$PLIST_PATH")"
    python3 - "$MODE" "$REPORTER_DST" "$PLIST_PATH" "$MUNKIREPORT_URL" "$SIMPLEMDM_CLIENT_SECRET" "$SIMPLEMDM_DEVICE_TOKEN" "$CLIENT_VERSION" "$SOURCE_NAME" "$START_INTERVAL" <<'PY'
import os
import plistlib
import sys

mode, reporter_dst, plist_path, url, secret, device_token, version, source, interval = sys.argv[1:]

program_args = ["/bin/sh", reporter_dst]
if mode == "hardened":
    program_args = [reporter_dst]

env = {
    "MUNKIREPORT_URL": url,
    "SIMPLEMDM_CLIENT_SECRET": secret,
    "SIMPLEMDM_CLIENT_VERSION": version,
    "SIMPLEMDM_CLIENT_SOURCE": source,
}
if device_token:
    env["SIMPLEMDM_DEVICE_TOKEN"] = device_token

plist = {
    "Label": "com.googlecode.munkireport-simplemdm-client-reporter",
    "ProgramArguments": program_args,
    "EnvironmentVariables": env,
    "StartInterval": int(interval),
    "RunAtLoad": True,
    "StandardOutPath": "/var/log/simplemdm_client_reporter.log",
    "StandardErrorPath": "/var/log/simplemdm_client_reporter.log",
}

with open(plist_path, "wb") as fh:
    plistlib.dump(plist, fh)
PY
    chmod 644 "$PLIST_PATH"
    echo "Installed LaunchDaemon plist:"
    echo "  $PLIST_PATH"
fi

if [ "$INSTALL_POSTFLIGHT" = "1" ]; then
    mkdir -p "$POSTFLIGHT_DIR"
    POSTFLIGHT_DST="$POSTFLIGHT_DIR/postflight_simplemdm_client_reporter_example.sh"
    cp "$SCRIPT_DIR/postflight_simplemdm_client_reporter_example.sh" "$POSTFLIGHT_DST"
    chmod 755 "$POSTFLIGHT_DST"
    python3 - "$POSTFLIGHT_DST" "$MUNKIREPORT_URL" "$SIMPLEMDM_CLIENT_SECRET" "$CLIENT_VERSION" "$SOURCE_NAME" "$REPORTER_DST" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
url = sys.argv[2]
secret = sys.argv[3]
version = sys.argv[4]
source = sys.argv[5]
reporter = sys.argv[6]

text = path.read_text()
text = text.replace('https://munkireport.example.com', url)
text = text.replace('replace-me', secret)
text = text.replace('munki-postflight-1.0.0', version)
text = text.replace('munki_postflight', source)
text = text.replace('/usr/local/munki/simplemdm_client_reporter_example.sh', reporter)
path.write_text(text)
PY
    echo "Installed Munki postflight wrapper:"
    echo "  $POSTFLIGHT_DST"
fi

echo
echo "Next steps:"
echo "  1. Confirm Option B is enabled in Admin -> SimpleMDM Settings."
echo "  2. If using hardened mode, confirm HMAC/replay/device-token settings match the client."
if [ "$INSTALL_LAUNCHD" = "1" ]; then
    echo "  3. Load the LaunchDaemon:"
    echo "     sudo launchctl bootstrap system $PLIST_PATH"
    echo "     sudo launchctl enable system/com.googlecode.munkireport-simplemdm-client-reporter"
fi
if [ "$INSTALL_POSTFLIGHT" = "1" ]; then
    echo "  4. Confirm Munki runs the postflight wrapper from:"
    echo "     $POSTFLIGHT_DIR"
fi
