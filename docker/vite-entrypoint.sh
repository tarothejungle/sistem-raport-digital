#!/bin/sh
set -eu

cd /var/www/html

lock_hash="$(sha256sum package-lock.json | awk '{print $1}')"
installed_hash="$(cat node_modules/.package-lock.sha256 2>/dev/null || true)"

if [ ! -x node_modules/.bin/vite ] || [ "$lock_hash" != "$installed_hash" ]; then
    echo "Synchronizing npm dependencies..."
    npm ci
    printf '%s' "$lock_hash" > node_modules/.package-lock.sha256
fi

cleanup() {
    rm -f public/hot
}

trap cleanup EXIT INT TERM

npm run dev -- --host 0.0.0.0 --port 5173 &
vite_pid=$!
wait "$vite_pid"
