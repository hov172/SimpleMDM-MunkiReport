#!/bin/bash

# SimpleMDM module install script for MunkiReport
# This sets up the sync script on the MunkiReport server

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
echo "Add to crontab (every 15 minutes):"
echo "    */15 * * * * SIMPLEMDM_API_KEY='your-key' MUNKIREPORT_URL='https://mr.example.com' /usr/bin/python3 $SCRIPT_DIR/simplemdm_sync.py >> /var/log/simplemdm_sync.log 2>&1"
echo ""
