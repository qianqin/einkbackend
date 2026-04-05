#!/usr/bin/env bash
set -euo pipefail

# Deploy family dashboard plugin to BYOS server
#
# Usage: ./scripts/deploy-dashboard.sh
#
# Config: scripts/.env (not committed)
#   DEPLOY_HOST            — SSH host (required)
#   DEPLOY_USER            — SSH user (required)
#   DASHBOARD_PLUGIN_UUID  — Plugin UUID to update (required)

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
DASHBOARD_DIR="$PROJECT_DIR/dashboard-api"

# Load local config
if [ -f "$SCRIPT_DIR/.env" ]; then
    set -a; source "$SCRIPT_DIR/.env"; set +a
fi

DEPLOY_HOST="${DEPLOY_HOST:?Set DEPLOY_HOST in scripts/.env}"
DEPLOY_USER="${DEPLOY_USER:?Set DEPLOY_USER in scripts/.env}"
PLUGIN_UUID="${DASHBOARD_PLUGIN_UUID:?Set DASHBOARD_PLUGIN_UUID in scripts/.env}"

SSH_TARGET="${DEPLOY_USER}@${DEPLOY_HOST}"
CONTAINER=$(ssh "$SSH_TARGET" "docker ps -qf name=trmnl-app")

BLADE="$DASHBOARD_DIR/family-dashboard.blade.php"
FIELDS="$DASHBOARD_DIR/custom-fields.yaml"

if [ ! -f "$BLADE" ]; then
    echo "ERROR: $BLADE not found" >&2
    exit 1
fi

echo "==> Uploading dashboard template"
scp "$BLADE" "${SSH_TARGET}:/tmp/family-dashboard.blade.php"
scp "$FIELDS" "${SSH_TARGET}:/tmp/custom-fields.yaml"

echo "==> Updating plugin on server"
ssh "$SSH_TARGET" "
    docker cp /tmp/family-dashboard.blade.php '${CONTAINER}:/tmp/family-dashboard.blade.php'
    docker cp /tmp/custom-fields.yaml '${CONTAINER}:/tmp/custom-fields.yaml'
    docker exec '${CONTAINER}' php artisan tinker --execute=\"
        \\\$plugin = App\Models\Plugin::where('uuid', '${PLUGIN_UUID}')->firstOrFail();
        \\\$plugin->update([
            'render_markup' => file_get_contents('/tmp/family-dashboard.blade.php'),
            'configuration_template' => json_encode(['custom_fields' => Symfony\Component\Yaml\Yaml::parseFile('/tmp/custom-fields.yaml')]),
            'current_image' => null,
        ]);
        echo 'Family Dashboard updated (plugin ' . \\\$plugin->uuid . '). Image cache cleared.';
    \"
    rm -f /tmp/family-dashboard.blade.php /tmp/custom-fields.yaml
"

echo "==> Done. Dashboard will re-render on next device refresh."
