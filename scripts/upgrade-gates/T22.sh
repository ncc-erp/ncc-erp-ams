#!/usr/bin/env bash
# Gate for T22 - L9 - fideloper/proxy removed
gate_section "T22 - Laravel 9 trusted proxies"

assert_not_installed "fideloper/proxy"
assert_no_grep "Fideloper" app config
assert_grep 'Illuminate\\Http\\Middleware\\TrustProxies' "app/Http/Middleware/TrustProxies.php"
