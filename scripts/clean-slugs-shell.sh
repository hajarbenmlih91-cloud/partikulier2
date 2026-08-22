#!/bin/bash
WP="wp --path=/home/ubuntu/wp-6170-clean --allow-root"
POSTS=$($WP post list --post_type=properties --format=ids)
for ID in $POSTS; do
    TITLE=$($WP post get $ID --field=post_title)
    SLUG=$($WP post get $ID --field=post_name)
    
    NEW_TITLE=$(echo "$TITLE" | sed 's/إعلان مترجم: //g' | sed 's/Translated listing: //g')
    
    # Pour le slug, on décode d'abord
    DECODED_SLUG=$(python3 -c "import urllib.parse; print(urllib.parse.unquote('$SLUG'))")
    NEW_SLUG=$(echo "$DECODED_SLUG" | sed 's/إعلان-مترجم-//g' | sed 's/translated-listing-//g')
    
    if [ "$TITLE" != "$NEW_TITLE" ] || [ "$DECODED_SLUG" != "$NEW_SLUG" ]; then
        $WP post update $ID --post_title="$NEW_TITLE" --post_name="$NEW_SLUG"
        echo "Updated $ID: $NEW_TITLE ($NEW_SLUG)"
    fi
done
