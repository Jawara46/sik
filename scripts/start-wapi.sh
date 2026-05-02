#!/bin/zsh
set -euo pipefail

PROJECT_ROOT="/Applications/MAMP/htdocs/sik"
WAPI_DIR="$PROJECT_ROOT/WAPI"
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"
NODE_BIN="${NODE_BIN:-/opt/homebrew/bin/node}"

export PORT="${PORT:-3000}"
export WAPI_HOST="${WAPI_HOST:-127.0.0.1}"
export DB_SOCKET="${DB_SOCKET:-/Applications/MAMP/tmp/mysql/mysql.sock}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_PORT="${DB_PORT:-3306}"
export DB_USERNAME="${DB_USERNAME:-root}"
export DB_PASSWORD="${DB_PASSWORD:-root}"
export DB_DATABASE="${DB_DATABASE:-sik}"
export APP_PORTAL_URL="${APP_PORTAL_URL:-http://localhost:8888/sik/public}"

if /usr/sbin/lsof -nP -iTCP:"$PORT" -sTCP:LISTEN >/dev/null 2>&1; then
  echo "WAPI already listening on ${WAPI_HOST}:${PORT}"
  exit 0
fi

cd "$WAPI_DIR"
exec "$NODE_BIN" app.js
