#!/bin/sh
set -eu

CUSTOM_DIR="/var/www/html/assets/icons/custom"
TARGET_CONFIG="$CUSTOM_DIR/icons.json"
DEFAULT_CONFIG="/var/www/html/config/icons.json"

mkdir -p "$CUSTOM_DIR"

if [ ! -f "$TARGET_CONFIG" ] && [ -f "$DEFAULT_CONFIG" ]; then
    cp "$DEFAULT_CONFIG" "$TARGET_CONFIG"
fi

chmod 666 "$TARGET_CONFIG" 2>/dev/null || true

exec "$@"
