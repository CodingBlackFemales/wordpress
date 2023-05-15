#!/bin/bash

# Exports from database, masks personal data and fixes legacy collations
# Uses UTF-8 character type fix from https://stackoverflow.com/a/23584470/494224
# TODO: Export from production environment
# TODO: Remove collation replacement after migration to 20i
wp db export - --complete-insert | masking | LC_ALL=C sed 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_520_ci/g' > "data/wordpress-$(date +'%Y%m%d%H%M%S').sql"
