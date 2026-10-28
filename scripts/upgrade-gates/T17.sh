#!/usr/bin/env bash
# Gate for T17 - drop the old Doctrine / lcobucci pins
gate_section "T17 - old Doctrine and lcobucci pins removed"

for pkg in doctrine/cache doctrine/common doctrine/inflector doctrine/instantiator \
           lcobucci/clock lcobucci/jwt phpdocumentor/reflection-docblock \
           phpspec/prophecy paragonie/constant_time_encoding; do
    assert_not_required "$pkg" "require"
    assert_not_required "$pkg" "require-dev"
done
