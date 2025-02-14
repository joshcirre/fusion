#!/bin/bash

# Get the directory where the script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Create a temporary directory for our work
temp_dir=$(mktemp -d)
trap 'rm -rf "$temp_dir"' EXIT

# Set up the package name
package_name="fusionphp-fusion-dev-main"

# Create the package directory structure
package_dir="$temp_dir/$package_name"
mkdir -p "$package_dir"

# Path to the source composer.json
source_composer="$SCRIPT_DIR/../composer.json"

if [ ! -f "$source_composer" ]; then
  echo "Error: composer.json not found in parent directory"
  exit 1
fi

# Copy composer.json to package directory
cp "$source_composer" "$package_dir/composer.json"

# Insert version field before the "type": "library" line
# Cross-platform compatible sed command
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS (BSD) version
    sed -i '' '/[[:space:]]*"type": "library",/i\
    "version": "dev-main",' "$package_dir/composer.json"
else
    # Linux (GNU) version
    sed -i '/[[:space:]]*"type": "library",/i\    "version": "dev-main",' "$package_dir/composer.json"
fi

# Create zip file
output_zip="$SCRIPT_DIR/$package_name.zip"

# Remove existing zip if it exists
if [ -f "$output_zip" ]; then
  rm "$output_zip"
fi

# Create the zip file from the parent of the package directory
cd "$temp_dir" && zip -v -r -q "$output_zip" "$package_name"

# Check if zip was successful
if [ $? -ne 0 ]; then
  echo "Error: Failed to create zip file"
  echo "Current directory: $(pwd)"
  echo "Contents of temp directory:"
  ls -la "$temp_dir"
  exit 1
fi