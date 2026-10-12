#!/usr/bin/env sh
set -eu

# Build and archive both DirectAdmin deployment packages.
# Usage:
#   ./make-deployment-archives.sh https://api.example.com

if [ "$#" -ne 1 ] || [ -z "$1" ]; then
    echo "Usage: $0 https://your-api-domain.example.com" >&2
    exit 2
fi

case "$1" in
    http://*|https://*) ;;
    *)
        echo "API URL must start with http:// or https://" >&2
        exit 2
        ;;
esac

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
export FRONTEND_API_BASE_URL=${1%/}

exec "$SCRIPT_DIR/deployment/directadmin/build-packages.sh"
