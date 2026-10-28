#!/usr/bin/env bash
# Gate for T30 - L10 - remaining breaking changes
gate_section "T30 - Laravel 10 closing checkpoint"

assert_no_grep 'dispatchNow\(' app
assert_no_grep '\$routeMiddleware' "app/Http/Kernel.php"
