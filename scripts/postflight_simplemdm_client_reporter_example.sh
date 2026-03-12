#!/bin/sh
set -eu

# Example Munki postflight hook for Option B.
#
# This wrapper calls the included shared-secret reporter after Munki completes.
# Adjust the environment variables below to match your deployment.
#
# Typical placement:
#   /usr/local/munki/postflight.d/postflight_simplemdm_client_reporter_example.sh

export MUNKIREPORT_URL="${MUNKIREPORT_URL:-https://munkireport.example.com}"
export SIMPLEMDM_CLIENT_SECRET="${SIMPLEMDM_CLIENT_SECRET:-replace-me}"
export SIMPLEMDM_CLIENT_VERSION="${SIMPLEMDM_CLIENT_VERSION:-munki-postflight-1.0.0}"
export SIMPLEMDM_CLIENT_SOURCE="${SIMPLEMDM_CLIENT_SOURCE:-munki_postflight}"

REPORTER_PATH="${REPORTER_PATH:-/usr/local/munki/simplemdm_client_reporter_example.sh}"
LOG_PATH="${LOG_PATH:-/var/log/simplemdm_client_reporter.log}"

if [ ! -x "$REPORTER_PATH" ]; then
    echo "SimpleMDM client reporter not found or not executable: $REPORTER_PATH" >> "$LOG_PATH"
    exit 0
fi

"$REPORTER_PATH" >> "$LOG_PATH" 2>&1 || true

exit 0
