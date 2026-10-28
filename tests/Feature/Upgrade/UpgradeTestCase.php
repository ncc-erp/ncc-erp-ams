<?php

namespace Tests\Feature\Upgrade;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Depreciation;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

/**
 * Base class of the regression smoke tests of the PHP/Laravel upgrade.
 *
 * Every test runs against a freshly migrated MySQL database and always has
 * the default settings available, because the application reads its whole
 * configuration from the settings table.
 */
abstract class UpgradeTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * A password that satisfies every password rule of the application.
     */
    public const STRONG_PASSWORD = 'Xq7!vZt2#Lm9@Rd4';

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSettingsExist();
        $this->ensureSetupCompleted();
    }

    /**
     * The application redirects every request to the setup wizard as long as
     * there is no user in the database.
     */
    protected function ensureSetupCompleted(): void
    {
        if (User::count() === 0) {
            $this->createSuperUser();
        }
    }

    /**
     * Creates the settings row of the application.
     *
     * The settings seeder of the application truncates the table first, a
     * truncate is a DDL statement and would commit the transaction of the
     * test, so the factory is used instead.
     */
    protected function ensureSettingsExist(): void
    {
        Cache::forget(Setting::APP_SETTINGS_KEY);

        if (! Setting::first()) {
            Setting::factory()->create();
        }
    }

    /**
     * A value that is unique in the whole test run.
     */
    protected function unique(string $prefix): string
    {
        return $prefix.'.'.str_replace('.', '', uniqid('', true));
    }

    protected function makeLocation(): Location
    {
        return Location::factory()->create();
    }

    protected function makeCompany(): Company
    {
        return Company::factory()->create();
    }

    protected function makeStatuslabel(): Statuslabel
    {
        return Statuslabel::factory()->create();
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::factory()->create();
    }

    /**
     * Creates an asset with all the relations the asset factory needs.
     */
    protected function makeAsset(array $attributes = []): Asset
    {
        $model = $this->makeAssetModel();

        return Asset::factory()->create(array_merge([
            'model_id' => $model->id,
            'rtd_location_id' => $this->makeLocation()->id,
            'supplier_id' => $this->makeSupplier()->id,
            'status_id' => $this->makeStatuslabel()->id,
            'asset_tag' => $this->unique('GATE-ASSET'),
        ], $attributes));
    }

    /**
     * Creates an asset model with all the relations the asset needs.
     */
    protected function makeAssetModel(): AssetModel
    {
        $category = Category::factory()->create(['category_type' => 'asset']);
        $manufacturer = Manufacturer::factory()->create();
        $depreciation = Depreciation::factory()->create();

        return AssetModel::factory()->create([
            'category_id' => $category->id,
            'manufacturer_id' => $manufacturer->id,
            'depreciation_id' => $depreciation->id,
        ]);
    }

    protected function createSuperUser(array $attributes = []): User
    {
        $company = $this->makeCompany();
        $username = $this->unique('gate.superuser');

        $user = User::factory()->create(array_merge([
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make(self::STRONG_PASSWORD),
            'permissions' => '{"superuser":"1"}',
            'activated' => 1,
            'company_id' => $company->id,
            'location_id' => $this->makeLocation()->id,
        ], $attributes));

        $this->assertTrue($user->exists, 'the super user could not be created');

        return $user;
    }

    /**
     * Makes sure that a personal access client exists, without it Passport is
     * not able to issue personal access tokens.
     */
    protected function ensurePersonalAccessClient(): void
    {
        $clients = app(ClientRepository::class);

        try {
            $clients->personalAccessClient();
        } catch (\RuntimeException $exception) {
            $clients->createPersonalAccessClient(null, 'Upgrade Gate Client', 'http://localhost');
        }
    }

    protected function apiTokenFor(User $user): string
    {
        $this->ensurePersonalAccessClient();

        return $user->createToken('upgrade-gate')->accessToken;
    }

    /**
     * @return array<string, string>
     */
    protected function apiHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiTokenFor($user),
            'Accept' => 'application/json',
        ];
    }
}
