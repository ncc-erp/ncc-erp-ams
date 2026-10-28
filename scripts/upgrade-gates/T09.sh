#!/usr/bin/env bash
# Gate for T09 - patch SAML (critical)
gate_section "T09 - SAML patched"

assert_min_version "onelogin/php-saml" "3.8.1"
assert_no_advisory "onelogin/php-saml"
assert_no_advisory "robrichards/xmlseclibs"
run_tests "SamlMetadataTest"
