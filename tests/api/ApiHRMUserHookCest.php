<?php

use Codeception\Util\HttpCode;
use Faker\Factory;

class ApiHRMUserHookCest
{
    private $email;
    private $createUserPayload;
    private $updateUserPayload;
    private $confirmPayload;
    private $username;
    private $faker;


    public function __construct()
    {
        $this->faker = Factory::create();
        $this->email = "test123" . rand(1000, 9999) . "@example.com";
        $this->username = substr($this->email, 0, strpos($this->email, '@'));
        $this->createUserPayload = [
            "sex" => $this->faker->randomElement([1, 2]),
            "type" => $this->faker->randomElement([1, 2]),
            "emailAddress" => $this->email,
            "fullName" => $this->faker->name,
            "branchCode" => $this->faker->randomElement(["DN001", "DN002", "DN003"]),
            "levelCode" => $this->faker->randomElement(["L1", "L2", "L3"]),
            "positionCode" => $this->faker->randomElement(["P01", "P02", "P03"]),
            "workingStartDate" => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            "skillNames" => $this->faker->randomElements(["C#", ".NET", "SQL"], 3)
        ];
        $this->updateUserPayload = [
            "sex" => $this->faker->randomElement([1, 2]),
            "type" => $this->faker->randomElement([1, 2]),
            "emailAddress" => $this->email,
            "fullName" => $this->faker->name,
            "branchCode" => $this->faker->randomElement(["DN001", "DN002", "DN003"]),
            "levelCode" => $this->faker->randomElement(["L1", "L2", "L3"]),
            "positionCode" => $this->faker->randomElement(["P01", "P02", "P03"]),
            "workingStartDate" => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
            "skillNames" => $this->faker->randomElements(["C#", ".NET", "SQL"], 3)
        ];
        $this->confirmPayload = [
            "emailAddress" => $this->email,
            "dateAt" => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s')
        ];
    }

    public function _before(ApiTester $I)
    {
        $this->user = \App\Models\User::find(1);
        $I->haveHttpHeader('Accept', 'application/json');
        $I->amBearerAuthenticated($I->getToken($this->user));
        $this->faker = Factory::create();
    }

    public function _after(ApiTester $I)
    {
        $I->deleteHeader('Authorization');
    }


    // Test create user successfully
    public function testCreateUserByHRMSuccess(ApiTester $I)
    {
        $I->wantTo('Create a new user via HRM hook');
        
        $this->cleanupTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $this->createUserPayload);
        
        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User created successfully'
        ]);
        
        // Verify user data in response
        $I->seeResponseContainsJson([
            'payload' => [
                'email' => $this->email,
                'username' => $this->username 
            ]
        ]);
    }

    // Test create user when user already exists
    public function testCreateUserByHRMUserAlreadyExists(ApiTester $I)
    {
        $I->wantTo('Try to create user that already exists');
        
        // First create the user
        $this->cleanupTestUser($I);
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $this->createUserPayload);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Try to create same user again
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $this->createUserPayload);
        
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User already exists'
        ]);
    }

    // Test update user successfully
    public function testUpdateUserByHRMSuccess(ApiTester $I)
    {
        $I->wantTo('Update existing user via HRM hook');
        
        // First create the user
        $this->cleanupTestUser($I);
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $this->createUserPayload);
        $I->seeResponseCodeIs(HttpCode::CREATED);
        
        // Now update the user
        $I->sendPost('services/app/Hrmv2/UpdateUserByHRM', $this->updateUserPayload);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User updated successfully'
        ]);
        
        $I->seeResponseContainsJson([
            'payload' => [
                'email' => $this->email
            ]
        ]);
    }

    // Test update user when user doesn't exist
    public function testUpdateUserByHRMUserNotFound(ApiTester $I)
    {
        $I->wantTo('Try to update user that does not exist');
        
        $this->cleanupTestUser($I);
        
        $I->sendPost('services/app/Hrmv2/UpdateUserByHRM', $this->updateUserPayload);
        
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User not found'
        ]);
    }

    // Test confirm user quit
    public function testConfirmUserQuit(ApiTester $I)
    {
        $I->wantTo('Confirm user quit status');
        
        // Create user first
        $this->createTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/ConfirmUserQuit', $this->confirmPayload);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User quit status confirmed successfully'
        ]);
        
        // Verify user is deactivated
        $I->seeResponseContainsJson([
            'payload' => [
                'activated' => false
            ]
        ]);
    }

    // Test confirm user pause
    public function testConfirmUserPause(ApiTester $I)
    {
        $I->wantTo('Confirm user pause status');
        
        $this->createTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/ConfirmUserPause', $this->confirmPayload);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User pause status confirmed successfully'
        ]);
        
        $I->seeResponseContainsJson([
            'payload' => [
                'activated' => false
            ]
        ]);
    }

    // Test confirm user maternity leave
    public function testConfirmUserMaternityLeave(ApiTester $I)
    {
        $I->wantTo('Confirm user maternity leave status');
        
        $this->createTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/ConfirmUserMaternityLeave', $this->confirmPayload);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User maternity leave status confirmed successfully'
        ]);
        
        $I->seeResponseContainsJson([
            'payload' => [
                'activated' => true
            ]
        ]);
    }

    // Test confirm user back to work
    public function testConfirmUserBackToWork(ApiTester $I)
    {
        $I->wantTo('Confirm user back to work status');
        
        $this->createTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/ConfirmUserBackToWork', $this->confirmPayload);
        
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User back to work status confirmed successfully'
        ]);
        
        // User should be activated
        $I->seeResponseContainsJson([
            'payload' => [
                'activated' => true
            ]
        ]);
    }

    // Test confirm status for non-existent user
    public function testConfirmStatusUserNotFound(ApiTester $I)
    {
        $I->wantTo('Try to confirm status for non-existent user');
        
        $this->cleanupTestUser($I);
        
        $I->sendPOST('services/app/Hrmv2/ConfirmUserQuit', $this->confirmPayload);
        
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'messages' => 'User not found'
        ]);
    }

    // Test with invalid email format
    public function testCreateUserWithInvalidEmail(ApiTester $I)
    {
        $I->wantTo('Try to create user with invalid email');
        
        $invalidPayload = $this->createUserPayload;
        $invalidPayload['emailAddress'] = 'invalid-email';
        
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $invalidPayload);
        
        $I->seeResponseIsJson();
    }


    // Helper method to create test user
    private function createTestUser(ApiTester $I)
    {
        $this->cleanupTestUser($I);
        $I->sendPOST('services/app/Hrmv2/CreateUserByHRM', $this->createUserPayload);
        $I->seeResponseCodeIs(HttpCode::CREATED);
    }

    // Helper method to cleanup test user
    private function cleanupTestUser(ApiTester $I)
    {
        try {
            $I->deleteInDatabase('users', ['email' => $this->email]);
        } catch (Exception $e) {
        }
    }
}
