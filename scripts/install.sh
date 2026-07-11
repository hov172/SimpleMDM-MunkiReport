#!/bin/bash

# SimpleMDM module install script for MunkiReport
# This sets up the sync script on the MunkiReport server
#
# MunkiReport inlines this script into the generated client installer
# (index.php?/install). Data for this module is pushed server-side by
# simplemdm_sync.py, so there is nothing to install on clients — and an
# `exit` here would abort the whole client installer. Detect the inlined
# context (the outer installer defines MUNKIPATH) and no-op.

if [ -n "${MUNKIPATH}" ]; then
    echo "  simplemdm is a server-side module; no client components to install."
    return 0 2>/dev/null || true
else

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DIR="$(dirname "$SCRIPT_DIR")"

echo "SimpleMDM Module Installer"
echo "=========================="
echo ""

# Check for Python 3
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 is required but not found."
    echo "Please install Python 3 and try again."
    exit 1
fi

echo "Python 3 found: $(python3 --version)"

# Check if the sync script exists
if [ ! -f "$SCRIPT_DIR/simplemdm_sync.py" ]; then
    echo "ERROR: simplemdm_sync.py not found in $SCRIPT_DIR"
    exit 1
fi

# Make sync script executable
chmod +x "$SCRIPT_DIR/simplemdm_sync.py"

echo ""
echo "Installation complete!"
echo ""
echo "Configuration:"
echo "  Set the following environment variables:"
echo "    export SIMPLEMDM_API_KEY='your-api-key-here'"
echo "    export MUNKIREPORT_URL='https://munkireport.example.com'"
echo "    export MUNKIREPORT_TOKEN='optional-api-token'"
echo ""
echo "Test with a dry run:"
echo "    python3 $SCRIPT_DIR/simplemdm_sync.py --dry-run --verbose"
echo ""
echo "Print a recommended cron entry:"
echo "    $SCRIPT_DIR/install_cron.sh --munkireport-url 'https://mr.example.com'"
echo ""
echo "Install/update the current user's cron entry:"
echo "    $SCRIPT_DIR/install_cron.sh --munkireport-url 'https://mr.example.com' --install"
echo ""

fi
