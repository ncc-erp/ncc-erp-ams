<?php

namespace Tests\Feature\Upgrade;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Importing users and assets from the CSV files of sample_csvs/.
 *
 * The sample files contain a thousand rows, the test imports the first rows of
 * them so that the import stays fast enough for the gate suite.
 */
class ImportTest extends UpgradeTestCase
{
    /**
     * Builds a CSV file with the header and the first $rows rows of a sample file.
     */
    protected function sampleCsv(string $sample, int $rows = 3): UploadedFile
    {
        $path = base_path('sample_csvs/'.$sample);
        $this->assertFileExists($path);

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertNotEmpty($lines);

        $content = implode("\n", array_slice($lines, 0, $rows + 1))."\n";

        $temporary = tempnam(sys_get_temp_dir(), 'gate-import');
        file_put_contents($temporary, $content);

        return new UploadedFile($temporary, $sample, 'text/csv', null, true);
    }

    public function testUsersCanBeImportedFromTheSampleCsv(): void
    {
        $admin = $this->createSuperUser();
        $headers = $this->apiHeaders($admin);

        $upload = $this->postJson('/api/v1/imports', [
            'files' => [$this->sampleCsv('MOCK_USERS.csv')],
        ], $headers);

        $upload->assertStatus(200);

        $importId = $upload->json('files.0.id');
        $this->assertNotEmpty($importId);

        $response = $this->postJson('/api/v1/imports/process/'.$importId, [
            'import-type' => 'user',
            'column-mappings' => [
                'First Name' => 'first_name',
                'Last Name' => 'last_name',
                'email' => 'email',
                'User name' => 'username',
                'Location' => 'location',
                'Phone Num' => 'phone_number',
                'Job Title For User' => 'jobtitle',
                'Employee Number' => 'employee_num',
                'Company' => 'company',
            ],
        ], $headers);

        $response->assertStatus(200);

        $this->assertGreaterThan(0, User::where('username', 'like', 'b%')->count());
        $this->assertDatabaseHas('users', ['username' => 'bocollopy0']);
    }

    public function testAssetsCanBeImportedFromTheSampleCsv(): void
    {
        $admin = $this->createSuperUser();
        $headers = $this->apiHeaders($admin);

        // the asset importer uses the first status label as its default
        $this->makeStatuslabel();

        $upload = $this->postJson('/api/v1/imports', [
            'files' => [$this->sampleCsv('MOCK_ASSETS.csv')],
        ], $headers);

        $upload->assertStatus(200);

        $importId = $upload->json('files.0.id');
        $this->assertNotEmpty($importId);

        $response = $this->postJson('/api/v1/imports/process/'.$importId, [
            'import-type' => 'asset',
            'column-mappings' => [
                'Full Name' => 'full_name',
                'Email' => 'email',
                'Username' => 'username',
                'item Name' => 'item_name',
                'Category' => 'category',
                'Model name' => 'asset_model',
                'Manufacturer' => 'manufacturer',
                'Model Number' => 'model_number',
                'Serial Number' => 'serial_number',
                'Asset Tag' => 'asset_tag',
                'Location' => 'location',
                'Notes' => 'notes',
                'Purchase Date' => 'purchase_date',
                'Purchase Cost' => 'purchase_cost',
                'Company' => 'company',
                'Status' => 'status',
                'Warranty Months' => 'warranty_months',
                'Supplier' => 'supplier',
            ],
        ], $headers);

        $response->assertStatus(200);

        $this->assertGreaterThan(0, Asset::count());
        $this->assertDatabaseHas('assets', ['asset_tag' => '970882174-8']);
    }
}
