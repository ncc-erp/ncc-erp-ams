#!/usr/bin/env bash
# Gate for T32 - replace laravelcollective/html
gate_section "T32 - laravelcollective/html replaced"

assert_not_installed "laravelcollective/html"
run_tests "FormPagesRenderTest"
