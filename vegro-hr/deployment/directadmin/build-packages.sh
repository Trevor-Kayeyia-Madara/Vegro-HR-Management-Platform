#!/usr/bin/env sh
set -eu

# Creates two upload-ready ZIP files for DirectAdmin shared hosting.
# Usage:
#   FRONTEND_API_BASE_URL=https://api.example.com ./deployment/directadmin/build-packages.sh

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
PROJECT_ROOT=$(CDPATH= cd -- "$SCRIPT_DIR/../.." && pwd)
OUTPUT_DIR="$SCRIPT_DIR/release"
STAGING_DIR="$OUTPUT_DIR/.staging"
FRONTEND_API_BASE_URL=${FRONTEND_API_BASE_URL:-}

if [ -z "$FRONTEND_API_BASE_URL" ]; then
    echo "FRONTEND_API_BASE_URL is required (for example: https://api.example.com)." >&2
    exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
    echo "The zip command is required to create DirectAdmin packages." >&2
    exit 1
fi

rm -rf "$STAGING_DIR"
mkdir -p "$STAGING_DIR/backend/laravel" "$STAGING_DIR/backend/public_html" "$STAGING_DIR/frontend/public_html"

# Keep Laravel source and dependencies above public_html. Secrets, development
# dependencies, containers, and the frontend source are intentionally omitted.
tar \
    --exclude='./.git' \
    --exclude='./.env*' \
    --exclude='./.idea' \
    --exclude='./.vscode' \
    --exclude='./deployment' \
    --exclude='./docker' \
    --exclude='./node_modules' \
    --exclude='./postman' \
    --exclude='./public' \
    --exclude='./tests' \
    --exclude='./vegro-hr-frontend' \
    -C "$PROJECT_ROOT" -cf - . | tar -C "$STAGING_DIR/backend/laravel" -xf -

cp "$SCRIPT_DIR/backend/.env.example" "$STAGING_DIR/backend/laravel/.env.example"

# Only known-safe public files are exposed. The wrapper index points back to
# ../laravel, which keeps application code and .env outside the document root.
cp "$SCRIPT_DIR/backend/index.php" "$STAGING_DIR/backend/public_html/index.php"
cp "$SCRIPT_DIR/backend/deploy.php" "$STAGING_DIR/backend/public_html/deploy.php"
cp "$PROJECT_ROOT/public/.htaccess" "$STAGING_DIR/backend/public_html/.htaccess"
[ -f "$PROJECT_ROOT/public/favicon.ico" ] && cp "$PROJECT_ROOT/public/favicon.ico" "$STAGING_DIR/backend/public_html/"
[ -f "$PROJECT_ROOT/public/robots.txt" ] && cp "$PROJECT_ROOT/public/robots.txt" "$STAGING_DIR/backend/public_html/"

(
    cd "$PROJECT_ROOT/vegro-hr-frontend"
    VITE_API_BASE_URL="$FRONTEND_API_BASE_URL" npm run build
)
cp -R "$PROJECT_ROOT/vegro-hr-frontend/dist/." "$STAGING_DIR/frontend/public_html/"
cp "$SCRIPT_DIR/frontend/.htaccess" "$STAGING_DIR/frontend/public_html/.htaccess"

rm -f "$OUTPUT_DIR/vegro-hr-backend-directadmin.zip" "$OUTPUT_DIR/vegro-hr-frontend-directadmin.zip"
(
    cd "$STAGING_DIR/backend"
    zip -qr "$OUTPUT_DIR/vegro-hr-backend-directadmin.zip" laravel public_html
)
(
    cd "$STAGING_DIR/frontend"
    zip -qr "$OUTPUT_DIR/vegro-hr-frontend-directadmin.zip" public_html
)

rm -rf "$STAGING_DIR"

echo "Created: $OUTPUT_DIR/vegro-hr-backend-directadmin.zip"
echo "Created: $OUTPUT_DIR/vegro-hr-frontend-directadmin.zip"
