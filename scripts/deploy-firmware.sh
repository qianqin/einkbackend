#!/usr/bin/env bash
set -euo pipefail

# Deploy firmware to BYOS server for OTA
#
# Usage: ./scripts/deploy-firmware.sh
#
# Config: scripts/.env (not committed)
#   DEPLOY_HOST  — SSH host (required)
#   DEPLOY_USER  — SSH user (required)
#   PIO_ENV      — PlatformIO environment (default: lilygo_t7_s3)

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
FIRMWARE_DIR="$PROJECT_DIR/firmware"

# Load local config (not committed — see .gitignore *.env*)
if [ -f "$SCRIPT_DIR/.env" ]; then
    set -a; source "$SCRIPT_DIR/.env"; set +a
fi

DEPLOY_HOST="${DEPLOY_HOST:?Set DEPLOY_HOST in scripts/.env}"
DEPLOY_USER="${DEPLOY_USER:?Set DEPLOY_USER in scripts/.env}"
PIO_ENV="${PIO_ENV:-lilygo_t7_s3}"

SSH_TARGET="${DEPLOY_USER}@${DEPLOY_HOST}"

# --- Extract version from config.h ---
CONFIG_H="$FIRMWARE_DIR/include/config.h"
MAJOR=$(grep '#define FW_MAJOR_VERSION' "$CONFIG_H" | awk '{print $3}')
MINOR=$(grep '#define FW_MINOR_VERSION' "$CONFIG_H" | awk '{print $3}')
PATCH=$(grep '#define FW_PATCH_VERSION' "$CONFIG_H" | awk '{print $3}')
VERSION="${MAJOR}.${MINOR}.${PATCH}"

echo "==> Building firmware ${VERSION} (env: ${PIO_ENV})"
cd "$FIRMWARE_DIR"
pio run -e "$PIO_ENV"

BIN_PATH="$FIRMWARE_DIR/.pio/build/${PIO_ENV}/firmware.bin"
if [ ! -f "$BIN_PATH" ]; then
    echo "ERROR: Build output not found at $BIN_PATH" >&2
    exit 1
fi

BIN_SIZE=$(wc -c < "$BIN_PATH" | tr -d ' ')
echo "==> Built: firmware.bin (${BIN_SIZE} bytes)"

# --- Upload to server ---
REMOTE_TMP="/tmp/firmware-${VERSION}.bin"
echo "==> Uploading to ${SSH_TARGET}:${REMOTE_TMP}"
scp "$BIN_PATH" "${SSH_TARGET}:${REMOTE_TMP}"

# --- Deploy on server via tinker (works with upstream byos_laravel image) ---
CONTAINER=$(ssh "$SSH_TARGET" "docker ps -qf name=trmnl-app")
echo "==> Deploying firmware ${VERSION} to container ${CONTAINER:0:12}"

ssh "$SSH_TARGET" "
    docker cp '${REMOTE_TMP}' '${CONTAINER}:/tmp/firmware.bin'
    docker exec '${CONTAINER}' php artisan tinker --execute=\"
        use Illuminate\Support\Facades\Storage;
        if (!Storage::disk('public')->exists('firmwares')) Storage::disk('public')->makeDirectory('firmwares');
        \\\$content = file_get_contents('/tmp/firmware.bin');
        Storage::disk('public')->put('firmwares/FW${VERSION}.bin', \\\$content);
        App\Models\Firmware::where('latest', true)->update(['latest' => false]);
        \\\$fw = App\Models\Firmware::updateOrCreate(['version_tag' => '${VERSION}'], ['storage_location' => 'firmwares/FW${VERSION}.bin', 'latest' => true]);
        \\\$count = App\Models\Device::query()->update(['update_firmware_id' => \\\$fw->id]);
        echo \\\"Firmware ${VERSION} deployed. \\\$count device(s) flagged for OTA.\\\";
    \"
    rm -f '${REMOTE_TMP}'
"

echo "==> Done. Devices will OTA to ${VERSION} on next refresh."
