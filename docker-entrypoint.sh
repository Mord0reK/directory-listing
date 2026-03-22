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

# Make bind-mounted directories writable.
# Only chown if root-owned (Docker auto-created), otherwise preserve host ownership.
# chmod 777 ensures www-data can write regardless of ownership.
for dir in /var/www/html/content /var/www/html/assets/icons/custom; do
    if [ -d "$dir" ]; then
        if [ "$(stat -c %u "$dir")" = "0" ]; then
            chown -R www-data:www-data "$dir"
        fi
        chmod -R 777 "$dir"
    fi
done

exec "$@"
