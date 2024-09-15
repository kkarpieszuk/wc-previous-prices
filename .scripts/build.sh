#!/usr/bin/env bash

set -e

git checkout develop
git pull

# Get the plugin version from the readme.txt file.
PLUGIN_VERSION=$(grep -oP '(?<=Stable tag: ).*' readme.txt)

# Prompt to confirm the version number before continuing.
read -r -p "Plugin version: $PLUGIN_VERSION. Continue? [y/N] " response
case "$response" in
    [yY][eE][sS]|[yY])
        echo "Continuing..."
        ;;
    *)
        exit 1
        ;;
esac

# make build directory and copy all files to it.
rm -rf build
mkdir build
mkdir build/gitversion
rsync -av --exclude='build' . build/gitversion/

# Go to the build directory.
cd build/gitversion

# Run composer install.
composer install --no-dev

# make pot file.
wp i18n make-pot . languages/wc-price-history.pot

# Remove not needed files.
rm -rf .git .github .husky .scripts node_modules \
 tests .gitignore .phpunit.result.cache composer.* \
 package-lock.json package.json phpunit.xml \
 phpstan.neon phpstan.neon.dist README.md screenshot-1.png

