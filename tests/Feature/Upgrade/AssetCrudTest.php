<?php

namespace Tests\Feature\Upgrade;

/**
 * Creating, updating and deleting an asset through the API.
 */
class AssetCrudTest extends UpgradeTestCase
{
    public function testAnAssetCanBeCreatedThroughTheApi(): void
    {
        $user = $this->createSuperUser();
        $model = $this->makeAssetModel();

        $response = $this->postJson('/api/v1/hardware', [
            'model_id' => $model->id,
            'name' => 'Gate Regression Laptop',
            'serial' => 'GATE-SERIAL-0001',
            'asset_tag' => 'GATE-0001',
            'status_id' => $this->makeStatuslabel()->id,
            'supplier_id' => $this->makeSupplier()->id,
        ], $this->apiHeaders($user));

        $response->assertStatus(200);

        $this->assertDatabaseHas('assets', [
            'name' => 'Gate Regression Laptop',
            'serial' => 'GATE-SERIAL-0001',
        ]);
    }

    public function testAnAssetCanBeReadThroughTheApi(): void
    {
        $user = $this->createSuperUser();
        $asset = $this->makeAsset(['name' => 'Gate Regression Laptop']);

        $response = $this->getJson('/api/v1/hardware/'.$asset->id, $this->apiHeaders($user));

        $response->assertStatus(200);
        $this->assertSame('Gate Regression Laptop', $response->json('name'));
    }

    public function testAnAssetCanBeUpdatedThroughTheApi(): void
    {
        $user = $this->createSuperUser();
        $asset = $this->makeAsset(['name' => 'Gate Regression Laptop']);

        $response = $this->putJson('/api/v1/hardware/'.$asset->id, [
            'name' => 'Gate Regression Laptop (renamed)',
        ], $this->apiHeaders($user));

        $response->assertStatus(200);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Gate Regression Laptop (renamed)',
        ]);
    }

    public function testAnAssetCanBeDeletedThroughTheApi(): void
    {
        $user = $this->createSuperUser();
        $asset = $this->makeAsset(['name' => 'Gate Regression Laptop']);

        $response = $this->deleteJson('/api/v1/hardware/'.$asset->id, [], $this->apiHeaders($user));

        $response->assertStatus(200);

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }
}
