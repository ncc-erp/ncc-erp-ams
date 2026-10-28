#!/usr/bin/env bash
# Gate for T25 - L9 - SwiftMailer to Symfony Mailer
gate_section "T25 - Laravel 9 mailer"

assert_no_grep "Swift_|withSwiftMessage|SwiftMailer" app config resources
run_tests "MailTest"
