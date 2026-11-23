#!/bin/sh
set -eu
release=$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)
docroot=$1
public="$docroot/private/nis2"
admin="$docroot/privat/nis2"
test ! -e "$public"
test ! -e "$admin"
mkdir -p "$public/assets" "$public/api" "$admin/assets" "$admin/api"
cp "$release/deployment/participant.htaccess" "$public/.htaccess"
cp "$release/deployment/facilitator.htaccess" "$admin/.htaccess"
cp "$release/index.html" "$public/"
cp "$release/assets/nis2-client.js" "$public/assets/"
for file in _db.php answer.php session.php; do
    cp "$release/api/$file" "$public/api/"
done
cp "$release/facilitator.php" "$release/facilitator.html" "$admin/"
cp "$release/assets/nis2-admin.js" "$admin/assets/"
for file in _admin.php _db.php answers.php session-create.php session-reset.php session-round.php session-state.php; do
    cp "$release/api/$file" "$admin/api/"
done
