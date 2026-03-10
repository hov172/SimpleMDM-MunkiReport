#!/bin/bash

# SimpleMDM module uninstall script for MunkiReport

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_DIR="$(dirname "$SCRIPT_DIR")"

echo "SimpleMDM Module Uninstaller"
echo "============================"
echo ""

# Remove executable permission from sync script
if [ -f "$SCRIPT_DIR/simplemdm_sync.py" ]; then
    chmod -x "$SCRIPT_DIR/simplemdm_sync.py"
    echo "Removed executable bits from simplemdm_sync.py"
fi

echo ""
echo "Note: To completely uninstall, you should also:"
echo "1. Remove the crontab entry for simplemdm_sync.py"
echo "   Example: $SCRIPT_DIR/remove_cron.sh"
echo "2. Remove 'simplemdm' from your MODULES list in .env"
echo "3. (Optional) Run database migration rollback if needed"
echo ""
echo "Uninstallation prep complete."
