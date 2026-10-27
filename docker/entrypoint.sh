#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------
# 1. Bind nginx to the port Render assigns
# ---------------------------------------------------------------
PORT="${PORT:-10000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/nginx.conf
echo "[entrypoint] nginx will listen on ${PORT}"

# ---------------------------------------------------------------
# 2. Recreate ephemeral storage. Nothing on this filesystem survives
#    a restart, so these directories are gone on every cold start.
# ---------------------------------------------------------------
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------
# 2b. nginx's temp directories.
#
#     Also done in the Dockerfile; repeated here because getting it
#     wrong costs every file upload on the site and the symptom -
#     a 500 from an endpoint that never reaches PHP - points
#     nowhere near nginx. Cheap to assert, expensive to rediscover.
#
#     nginx.conf runs workers as www-data; Alpine's package owns
#     /var/lib/nginx as `nginx`. A POST body too large for
#     client_body_buffer_size is spooled to disk there first, so
#     every photo upload hits it.
# ---------------------------------------------------------------
mkdir -p /var/lib/nginx/tmp/client_body \
         /var/lib/nginx/tmp/proxy \
         /var/lib/nginx/tmp/fastcgi \
         /var/lib/nginx/tmp/uwsgi \
         /var/lib/nginx/tmp/scgi
chown -R www-data:www-data /var/lib/nginx

# ---------------------------------------------------------------
# 3. Warm caches at RUNTIME, not build time.
#
#    config:cache freezes the values env() returns at the moment it
#    runs. During the Docker build none of Render's variables exist,
#    so caching then would bake in an app with no database.
# ---------------------------------------------------------------
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "[entrypoint] caches warmed"

# ---------------------------------------------------------------
# 4. Migrations, opt-in only.
#
#    This points at the shared Supabase database, so a migration here
#    is a migration for everyone. Left off, a failed migration cannot
#    crash-loop the container, and a cold start never touches the
#    schema.
# ---------------------------------------------------------------
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    # Retried, not run once. Supabase's pooler answers a burst of failed
    # logins with ECIRCUITBREAKER and refuses every connection for a while -
    # which is exactly what happens on the deploy that fixes a wrong
    # DB_PASSWORD, because the old instance is still failing to log in while
    # this one boots. Six tries 30 seconds apart rides that out instead of
    # failing the deploy and leaving the broken instance live.
    attempt=1
    until php artisan migrate --force --no-interaction; do
        if [ "$attempt" -ge 6 ]; then
            echo "[entrypoint] migrations failed after ${attempt} attempts"
            exit 1
        fi
        echo "[entrypoint] migrations failed (attempt ${attempt}); retrying in 30s"
        attempt=$((attempt + 1))
        sleep 30
    done
fi

# Seeding is deliberately absent. db:seed regenerates demo birds over
# the farm's real records, and there is no separate database to lose
# them from. Run seeders by hand, locally, never from a boot script.

# ---------------------------------------------------------------
# 5. Hand off to supervisor (php-fpm + nginx)
# ---------------------------------------------------------------
exec supervisord -c /etc/supervisord.conf
