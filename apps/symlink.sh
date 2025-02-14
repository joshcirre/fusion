#!/bin/bash

# Get the directory where the script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 <app-name>"
  exit 1
fi

APP_NAME=$1

# Function to convert gitattributes pattern to grep pattern
convert_pattern() {
  local pattern=$1
  pattern=${pattern% export-ignore}
  pattern=${pattern#/}
  pattern=${pattern%/}
  echo "^$pattern$"
}

# Create temporary file for exclude patterns
exclude_file=$(mktemp)

# Read .gitattributes and convert patterns
while IFS= read -r line || [[ -n "$line" ]]; do
  [[ -z "$line" || "$line" == \#* ]] && continue
  if [[ "$line" == *"export-ignore"* ]]; then
    pattern=$(convert_pattern "${line%% *}")
    echo "$pattern" >>"$exclude_file"
  fi
done <"$SCRIPT_DIR/../.gitattributes"

# Function to check if path should be excluded
should_exclude() {
  local path=$1
  while IFS= read -r pattern || [[ -n "$pattern" ]]; do
    if [[ $path =~ $pattern ]]; then
      return 0
    fi
  done <"$exclude_file"
  return 1
}

# Create target directory using absolute path
target_dir="$SCRIPT_DIR/$APP_NAME/vendor/fusionphp/fusion"

# Clear out existing directory if it exists
if [ -d "$target_dir" ]; then
  # echo "Clearing existing target directory..."
  rm -rf "$target_dir"
fi

# Create fresh directory
mkdir -p "$target_dir"

# Save current directory
ORIG_DIR=$(pwd)

# Change to source directory using absolute path
cd "$SCRIPT_DIR/.."
for item in *; do
  if ! should_exclude "$item"; then
    ln -s "$(pwd)/$item" "$target_dir/$item"
    # echo "Created symlink for $item"
    # else
    # echo "Skipping excluded item: $item"
  fi
done

# Return to original directory
cd "$ORIG_DIR"

# Clean up
rm "$exclude_file"
