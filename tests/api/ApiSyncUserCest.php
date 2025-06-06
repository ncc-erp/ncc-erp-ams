<?php

use App\Http\Controllers\Api\SyncListUserFromHRMController;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Mockery;
use Psr\Http\Message\ResponseInterface;

class ApiSyncUserCest
{
    protected $mockApiUrl;
    protected $client;
    protected $secretKeyApiUrl;
    protected $mailDomain;

    public function _before(ApiTester $I)
    {
        $this->mockApiUrl = getenv('HRM_API');
        $this->client = new Client();
        $this->secretKeyApiUrl = getenv('HRM_SECRET_KEY');
        $this->mailDomain = getenv('MAIL_DOMAIN');
        
        $this->cleanupTestData();
    }

    public function _after(ApiTester $I)
    {
        $this->cleanupTestData();
        Mockery::close();
    }

    public function testSyncListUserSuccess(ApiTester $I)
    {
        $existingUser = User::create([
            'username' => 'john.doe',
            'first_name' => 'Old First Name',
            'last_name' => 'Old Last Name',
            'email' => 'old.email@' . $this->mailDomain,
            'activated' => true,
            'permissions' => '{"superuser":"0","admin":"0"}',
        ]);

        $existingLocation = Location::create([
            'name' => 'Da Nang',
            'branch_code' => 'DN',
        ]);

        // Set up mock response
        $expectedResponse = $this->setupSuccessResponse();
        $mockedResponse = $this->createMockResponse(json_encode($expectedResponse), 200);
        
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('get')
            ->with($this->mockApiUrl, [
                'headers' => [
                    'X-Secret-Key' => $this->secretKeyApiUrl
                ],
                'verify' => false,
                'timeout' => 30
            ])
            ->once()
            ->andReturn($mockedResponse);

        $controller = new SyncListUserFromHRMController($mockClient);
        $request = new Request();
        $response = $controller->syncListUser($request);

        $responseData = json_decode($response->getContent(), true);
        $I->assertEquals('success', $responseData['status']);
        $I->assertEquals('User sync completed successfully', $responseData['messages']);
        $I->assertArrayHasKey('payload', $responseData);
        
        $syncStats = $responseData['payload'];
        $I->assertEquals(3, $syncStats['processed']);
        $I->assertEquals(2, $syncStats['created']); 
        $I->assertEquals(1, $syncStats['updated']); 
        $I->assertEquals(0, $syncStats['skipped']);

        $updatedUser = User::where('username', 'john.doe')->first();
        $I->assertNotNull($updatedUser);
        $I->assertEquals('John', $updatedUser->first_name);
        $I->assertEquals('Doe', $updatedUser->last_name);
        $I->assertEquals('john.doe@' . $this->mailDomain, $updatedUser->email);
        $I->assertEquals('Dev', $updatedUser->job_position_code);
        $I->assertEquals('TTS', $updatedUser->user_type);
        $I->assertEquals($existingLocation->id, $updatedUser->location_id);

        $newUser = User::where('username', 'jane.smith')->first();
        $I->assertNotNull($newUser);
        $I->assertEquals('Jane', $newUser->first_name);
        $I->assertEquals('Smith', $newUser->last_name);
        $I->assertEquals('jane.smith@' . $this->mailDomain, $newUser->email);
        $I->assertEquals('Tester', $newUser->job_position_code);
        $I->assertEquals('TTS', $newUser->user_type);
        $I->assertTrue($newUser->activated);

        $newLocation = Location::where('branch_code', 'HCM')->first();
        $I->assertNotNull($newLocation);
        $I->assertEquals('HCM', $newLocation->name);
        $I->assertEquals('HCM', $newLocation->branch_code);

        $userWithNewLocation = User::where('username', 'new.user')->first();
        $I->assertNotNull($userWithNewLocation);
        $I->assertEquals($newLocation->id, $userWithNewLocation->location_id);
    }

    public function testSyncListUserWithInvalidEmailDomain(ApiTester $I)
    {
        $expectedResponse = $this->setupResponseWithInvalidEmail();
        $mockedResponse = $this->createMockResponse(json_encode($expectedResponse), 200);
        
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockedResponse);

        $controller = new SyncListUserFromHRMController($mockClient);
        $request = new Request();
        $response = $controller->syncListUser($request);

        $responseData = json_decode($response->getContent(), true);
        $syncStats = $responseData['payload'];
        
        $I->assertEquals(2, $syncStats['processed']);
        $I->assertEquals(1, $syncStats['created']); 
        $I->assertEquals(0, $syncStats['updated']);
        $I->assertEquals(1, $syncStats['skipped']); 

        $invalidUser = User::where('email', 'invalid@wrongdomain.com')->first();
        $I->assertNull($invalidUser);

        $validUser = User::where('email', 'valid@' . $this->mailDomain)->first();
        $I->assertNotNull($validUser);
    }

    public function testSyncListUserApiError(ApiTester $I)
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andThrow(new Exception('API connection failed'));

        $controller = new SyncListUserFromHRMController($mockClient);
        $request = new Request();
        $response = $controller->syncListUser($request);

        $responseData = json_decode($response->getContent(), true);
        $I->assertEquals('error', $responseData['status']);
        $I->assertStringContainsString('API connection failed', $responseData['messages']);
    }

    public function testSyncListUserInvalidResponse(ApiTester $I)
    {
        $invalidResponse = ['error' => 'Invalid response'];
        $mockedResponse = $this->createMockResponse(json_encode($invalidResponse), 200);
        
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockedResponse);

        $controller = new SyncListUserFromHRMController($mockClient);
        $request = new Request();
        $response = $controller->syncListUser($request);

        $responseData = json_decode($response->getContent(), true);
        $I->assertEquals('error', $responseData['status']);
        $I->assertStringContainsString('Invalid response from HRM API', $responseData['messages']);
    }

    public function testSyncListUserMissingRequiredFields(ApiTester $I)
    {
        $expectedResponse = $this->setupResponseWithMissingFields();
        $mockedResponse = $this->createMockResponse(json_encode($expectedResponse), 200);
        
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('get')
            ->once()
            ->andReturn($mockedResponse);

        $controller = new SyncListUserFromHRMController($mockClient);
        $request = new Request();
        $response = $controller->syncListUser($request);

        $responseData = json_decode($response->getContent(), true);
        $syncStats = $responseData['payload'];
        
        $I->assertEquals(2, $syncStats['processed']);
        $I->assertEquals(1, $syncStats['created']); 
        $I->assertEquals(0, $syncStats['updated']);
        $I->assertEquals(1, $syncStats['skipped']);
    }

    protected function createMockResponse($body, $statusCode, $headers = [])
    {
        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->andReturn($body);
        $response->shouldReceive('getStatusCode')->andReturn($statusCode);
        $response->shouldReceive('getHeaders')->andReturn($headers);
        return $response;
    }

    protected function setupSuccessResponse()
    {
        return [
            'result' => [
                [
                    'email' => 'john.doe@' . $this->mailDomain,
                    'fullName' => 'John Doe',
                    'branchCode' => 'DN',
                    'jobPositionCode' => 'Dev',
                    'userType' => 0,
                    'userTypeName' => 'TTS',
                    'status' => 1,
                    'statusName' => 'Working',
                    'mezonId' => 'mezon123'
                ],
                [
                    'email' => 'jane.smith@' . $this->mailDomain,
                    'fullName' => 'Jane Smith',
                    'branchCode' => 'HCM',
                    'jobPositionCode' => 'Tester',
                    'userType' => 0,
                    'userTypeName' => 'TTS',
                    'status' => 1,
                    'statusName' => 'Working'
                ],
                [
                    'email' => 'new.user@' . $this->mailDomain,
                    'fullName' => 'New User Name',
                    'branchCode' => 'HCM',
                    'jobPositionCode' => 'Manager',
                    'userType' => 1,
                    'userTypeName' => 'Staff',
                    'status' => 1,
                    'statusName' => 'Working'
                ]
            ]
        ];
    }

    protected function setupResponseWithInvalidEmail()
    {
        return [
            'result' => [
                [
                    'email' => 'valid@' . $this->mailDomain,
                    'fullName' => 'Valid User',
                    'branchCode' => 'DN',
                    'jobPositionCode' => 'Dev',
                    'userTypeName' => 'TTS'
                ],
                [
                    'email' => 'invalid@wrongdomain.com',
                    'fullName' => 'Invalid User',
                    'branchCode' => 'DN',
                    'jobPositionCode' => 'Dev',
                    'userTypeName' => 'TTS'
                ]
            ]
        ];
    }

    protected function setupResponseWithMissingFields()
    {
        return [
            'result' => [
                [
                    'email' => 'complete@' . $this->mailDomain,
                    'fullName' => 'Complete User',
                    'branchCode' => 'DN',
                    'jobPositionCode' => 'Dev',
                    'userTypeName' => 'TTS'
                ],
                [
                    'email' => 'incomplete@' . $this->mailDomain,
                    // Missing fullName - should be skipped
                    'branchCode' => 'DN',
                    'jobPositionCode' => 'Dev',
                    'userTypeName' => 'TTS'
                ]
            ]
        ];
    }

    private function cleanupTestData()
    {
        // Clean up test users
        User::whereIn('username', ['john.doe', 'jane.smith', 'new.user', 'valid', 'complete'])
            ->delete();
        
        // Clean up test locations
        Location::whereIn('branch_code', ['DN', 'HCM', 'TEST'])
            ->delete();
    }
}
