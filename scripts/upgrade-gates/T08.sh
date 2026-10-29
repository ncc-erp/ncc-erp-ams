#!/usr/bin/env bash
# Gate for T08 - patch Livewire (RCE)
gate_section "T08 - Livewire patched"

assert_min_version "livewire/livewire" "2.12.7"
assert_no_advisory "livewire/livewire"
run_test_classes_once "LivewireLoginTest"
