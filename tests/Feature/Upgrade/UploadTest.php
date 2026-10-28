<?php

namespace Tests\Feature\Upgrade;

use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Uploading images (asset image and avatar) has to keep working and files that
 * are not images have to be rejected.
 */
class UploadTest extends UpgradeTestCase
{
    public function testAnAssetImageCanBeUploaded(): void
    {
        Storage::fake('public');

        $user = $this->createSuperUser();
        $model = $this->makeAssetModel();

        $response = $this->postJson('/api/v1/hardware', [
            'model_id' => $model->id,
            'name' => 'Gate Regression Laptop',
            'asset_tag' => 'GATE-UPLOAD-0001',
            'status_id' => $this->makeStatuslabel()->id,
            'supplier_id' => $this->makeSupplier()->id,
            'image' => UploadedFile::fake()->image('asset.png', 20, 20),
        ], $this->apiHeaders($user));

        $response->assertStatus(200);

        $asset = Asset::where('asset_tag', 'GATE-UPLOAD-0001')->first();

        $this->assertNotNull($asset);
        $this->assertNotEmpty($asset->image);
        Storage::disk('public')->assertExists('assets/'.$asset->image);
    }

    public function testAnAvatarCanBeUploaded(): void
    {
        Storage::fake('public');

        $user = $this->createSuperUser();

        $response = $this->actingAs($user)->post('/account/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => UploadedFile::fake()->image('avatar.png', 20, 20),
        ]);

        $response->assertRedirect();

        $user->refresh();

        $this->assertNotEmpty($user->avatar);
        Storage::disk('public')->assertExists('avatars/'.$user->avatar);
    }

    public function testAFileThatIsNotAnImageIsRejectedForAnAsset(): void
    {
        Storage::fake('public');

        $user = $this->createSuperUser();
        $model = $this->makeAssetModel();

        $response = $this->postJson('/api/v1/hardware', [
            'model_id' => $model->id,
            'name' => 'Gate Regression Laptop',
            'asset_tag' => 'GATE-UPLOAD-0002',
            'status_id' => $this->makeStatuslabel()->id,
            'supplier_id' => $this->makeSupplier()->id,
            'image' => UploadedFile::fake()->create('not-an-image.txt', 10),
        ], $this->apiHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('image');

        $this->assertDatabaseMissing('assets', ['asset_tag' => 'GATE-UPLOAD-0002']);
    }

    public function testAFileThatIsNotAnImageIsRejectedForAnAvatar(): void
    {
        Storage::fake('public');

        $user = $this->createSuperUser();

        $response = $this->actingAs($user)->post('/account/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'avatar' => UploadedFile::fake()->create('not-an-image.txt', 10),
        ]);

        $response->assertSessionHasErrors('avatar');
    }
}
