#!/usr/bin/env bash
# Gate for T06 - move debugbar to require-dev
gate_section "T06 - debugbar moved to require-dev"

assert_not_required "barryvdh/laravel-debugbar" "require"
assert_grep "^APP_DEBUG=false" ".env.example"

tmp="$(mktemp -d)"
cp composer.json composer.lock artisan "$tmp/" 2>/dev/null
for d in app bootstrap config database routes; do
    cp -r "$d" "$tmp/" 2>/dev/null
done
mkdir -p "$tmp/storage/framework" "$tmp/storage/logs" "$tmp/storage/app" "$tmp/public" "$tmp/resources/views"
cp .env.example "$tmp/.env"
php -r '
    $file = $argv[1];
    $contents = file_get_contents($file);
    $key = "base64:" . base64_encode(random_bytes(32));
    $contents = preg_replace("/^APP_KEY=.*$/m", "APP_KEY=$key", $contents);
    file_put_contents($file, $contents);
' "$tmp/.env"

if ( cd "$tmp" && composer install --no-dev --no-interaction --no-progress ) > /tmp/gate-t06-install.log 2>&1; then
    gate_record PASS "composer install --no-dev succeeds in a clean directory"
else
    tail -20 /tmp/gate-t06-install.log
    gate_record FAIL "composer install --no-dev succeeds in a clean directory" "see output above"
fi

if ( cd "$tmp" && APP_KEY="$(grep '^APP_KEY=' "$tmp/.env" | cut -d= -f2-)" php artisan about ) > /tmp/gate-t06-about.log 2>&1; then
    gate_record PASS "php artisan about works without dev dependencies"
else
    tail -20 /tmp/gate-t06-about.log
    gate_record FAIL "php artisan about works without dev dependencies" "see output above"
fi

rm -rf "$tmp"
