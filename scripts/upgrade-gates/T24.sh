#!/usr/bin/env bash
# Gate for T24 - L9 - Flysystem 1 to 3
gate_section "T24 - Laravel 9 Flysystem 3"

assert_min_version "league/flysystem" "3.0.0"
assert_not_installed "league/flysystem-cached-adapter"
assert_no_grep "'cache'[[:space:]]*=>" "config/filesystems.php"
run_tests "UploadTest"
