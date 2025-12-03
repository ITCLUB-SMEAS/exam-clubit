#!/bin/bash
# Generate PWA icons from source image
# Usage: ./scripts/generate-icons.sh source-image.png

SOURCE=$1
ICONS_DIR="public/icons"

if [ -z "$SOURCE" ]; then
    echo "Usage: ./scripts/generate-icons.sh source-image.png"
    exit 1
fi

mkdir -p $ICONS_DIR

SIZES="72 96 128 144 152 192 384 512"

for SIZE in $SIZES; do
    convert "$SOURCE" -resize ${SIZE}x${SIZE} "$ICONS_DIR/icon-${SIZE}x${SIZE}.png"
    echo "Generated icon-${SIZE}x${SIZE}.png"
done

echo "Done! Icons generated in $ICONS_DIR"
