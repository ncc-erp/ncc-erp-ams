<?php

namespace Tests\Feature\UpgradeExtra;

use App\Models\CustomFieldset;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Upgrade\UpgradeTestCase;

class CustomFieldSetDefaultValuesForModel extends UpgradeTestCase
{
    public function testTheComponentLoadsAndUpdatesItsFieldset(): void
    {
        $fieldset = CustomFieldset::factory()->create();
        $otherFieldset = CustomFieldset::factory()->create();
        $model = $this->makeAssetModel();
        $model->update(['fieldset_id' => $fieldset->id]);

        Livewire::test('custom-field-set-default-values-for-model', ['model_id' => $model->id])
            ->assertSet('model_id', $model->id)
            ->assertSet('fieldset_id', $fieldset->id)
            ->set('fieldset_id', $otherFieldset->id)
            ->assertSet('fieldset_id', $otherFieldset->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('custom_fieldsets', ['id' => $fieldset->id]);
        $this->assertSame($fieldset->id, DB::table('models')->where('id', $model->id)->value('fieldset_id'));
    }
}
