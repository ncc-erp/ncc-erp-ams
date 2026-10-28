#!/usr/bin/env bash
# Gate for T15 - drop mediconesystems/livewire-datatables
gate_section "T15 - mediconesystems/livewire-datatables removed"

assert_not_installed "mediconesystems/livewire-datatables"
assert_no_grep "Mediconesystems|livewire-datatable" app resources config
