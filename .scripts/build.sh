#!/usr/bin/env bash

set -e

function replace_version_number() {

  # Function to traverse directories and replace {VERSION} with the real version number
  traverse_and_replace() {
    for file in "$1"/*; do
      if [ -d "$file" ]; then
        # Skip /vendor and /node_modules directories
        if [[ "$file" == */vendor ]] || [[ "$file" == */node_modules ]]; then
          continue
        fi
        # Recursively traverse directories
        traverse_and_replace "$file"
      elif [ -f "$file" ]; then
        # Replace {VERSION} with the real version number in files
        sed -i "s/{VERSION}/$VERSION/g" "$file"
      fi
    done
  }

  # Start traversal from the current working directory
  traverse_and_replace "."

  echo "Replaced {VERSION} with $VERSION in all files."
}

git checkout develop
git pull

# Get the plugin version from the readme.txt file.
VERSION=$(grep -oP '(?<=Stable tag: ).*' readme.txt)

# Prompt to confirm the version number before continuing.
read -r -p "Plugin version: $VERSION. Continue? [y/N] " response
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
rm -rf vendor
composer install --no-dev

# make pot file.
wp i18n make-pot . languages/wc-price-history.pot

# Remove not needed files.
rm -rf .git .github .husky .scripts node_modules \
 tests .gitignore .phpunit.result.cache composer.* \
 package-lock.json package.json phpunit.xml \
 phpstan.neon phpstan.neon.dist README.md screenshot-1.png

replace_version_number

cd ..

# Checkout svn repository
svn checkout https://plugins.svn.wordpress.org/wc-price-history/ svn-checkout

# Copy files to svn repository
cp -r gitversion/* svn-checkout/trunk/

cd svn-checkout

svn status

# for each file in svn status marked with ? add it to svn
for file in $(svn status | grep '?' | awk '{print $2}'); do
  svn add $file
done

svn status

# svn ci -m "Release $VERSION"

# svn cp trunk tags/$VERSION

