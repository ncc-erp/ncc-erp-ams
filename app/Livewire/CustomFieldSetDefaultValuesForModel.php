<?php

namespace App\Livewire;

use App\Models\AssetModel;
use App\Models\CustomFieldset;
use Livewire\Component;

class CustomFieldSetDefaultValuesForModel extends Component
{
    public $add_default_values;
    public $fieldset_id;
    public $fields;
    public $model_id;
    public $model;

    public function mount(): void
    {
        if (is_null($this->model_id)) {
            return;
        }

        $this->model = AssetModel::find($this->model_id);
        $this->fieldset_id = $this->model->fieldset_id;
        $this->fields = null;

        if ($fieldset = CustomFieldset::find($this->fieldset_id)) {
            $this->fields = $fieldset->fields;
        }

        $this->add_default_values = $this->model->defaultValues->count() > 0;
    }

    public function updatedFieldsetId(): void
    {
        $fieldset = CustomFieldset::find($this->fieldset_id);
        $this->fields = $fieldset ? $fieldset->fields : null;
    }

    public function render()
    {
        return view('livewire.custom-field-set-default-values-for-model');
    }
}
