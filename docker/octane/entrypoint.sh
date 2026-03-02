#!/bin/bash
set -e

# Install npm dependencies if node_modules doesn't exist or package.json is newer
if [ ! -d "node_modules" ] || [ "package.json" -nt "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Execute the main command
exec "$@"
