#!/usr/bin/env bash
# Gate for T33 - Livewire 2 to 3
gate_section "T33 - Livewire 3"

assert_min_version "livewire/livewire" "3.5.2"
assert_no_advisory "livewire/livewire"
assert_no_grep '\$this->emit\(|\$emit\(' app resources/views
run_test_classes_once "LivewireLoginTest"
run_test_classes_once "CustomFieldSetDefaultValuesForModel"
