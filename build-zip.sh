#!/usr/bin/env bash
#
# Builds a WordPress-compatible ZIP where the root directory
# is always "easy-categories-shift64" regardless of version.
#
# Usage: bash build-zip.sh <version>

set -euo pipefail

PLUGIN_SLUG="easy-categories-shift64"
VERSION="${1:?Version argument required}"
DIST_DIR="/tmp/${PLUGIN_SLUG}"
ZIP_FILE="${PLUGIN_SLUG}.zip"

echo "Building ${ZIP_FILE} for version ${VERSION}..."

# Clean previous build artifacts
rm -rf "${DIST_DIR}" "${ZIP_FILE}"

# Create a temporary directory with the correct plugin slug name
mkdir -p "${DIST_DIR}"

# Copy all files, respecting .distignore
if command -v rsync &>/dev/null; then
    rsync -a \
        --exclude-from=".distignore" \
        --exclude=".releaserc.json" \
        --exclude="build-zip.sh" \
        ./ "${DIST_DIR}/"
else
    cp -R . "${DIST_DIR}/"
    # Remove items listed in .distignore
    while IFS= read -r pattern; do
        # Skip comments and empty lines
        [[ "${pattern}" =~ ^#.*$ || -z "${pattern}" ]] && continue
        rm -rf "${DIST_DIR:?}/${pattern}"
    done < .distignore
    rm -f "${DIST_DIR}/.releaserc.json"
    rm -f "${DIST_DIR}/build-zip.sh"
fi

# Build ZIP from the parent of the temp directory
# so the archive contains "easy-categories-shift64/" as root
(cd /tmp && zip -r "${OLDPWD}/${ZIP_FILE}" "${PLUGIN_SLUG}")

# Cleanup
rm -rf "${DIST_DIR}"

echo "Created ${ZIP_FILE} ($(du -h "${ZIP_FILE}" | cut -f1))"
