<?php

use App\Models\User;
use ApiTester;
use Codeception\Util\HttpCode;

class ApiMezonAuthCest
{
    private const AUTH_TIMEOUT_SECONDS = 300;

    public function _before(ApiTester $I)
    {
      $I->haveHttpHeader('Accept', 'application/json');
      $I->haveHttpHeader('Content-Type', 'application/json');
    }

    /**
     * Use MEZON_APP_TOKEN from .env.testing or default to 'MEZON_APP_TOKEN'
     */
    private function getMezonAppToken(): string
    {
        return env('MEZON_APP_TOKEN', 'MEZON_APP_TOKEN');
    }

    /**
     * Create HMAC signature similar to Controller logic
     */
    private function generateMezonHash(string $dataToCheck): string
    {
        $appSecret = $this->getMezonAppToken();

        // Step 1: MD5 app token
        $step1Md5 = md5($appSecret);
        
        // Step 2: Create Secret Key from WebAppData
        $secretKey = hash_hmac('sha256', 'WebAppData', $step1Md5, true);
        
        // Step 3: Hash data with Secret Key
        return hash_hmac('sha256', $dataToCheck, $secretKey);
    }

    /**
     * Create hashData payload already base64 encoded to send to server
     */
    private function createMezonHashData(array $params, ?string $forceSignature = null): string
    {
        $queryString = http_build_query($params);

        $hash = $forceSignature ?? $this->generateMezonHash($queryString);

        $finalString = $queryString . '&hash=' . $hash;

        return base64_encode($finalString);
    }

    /**
     * Helper to create User JSON string
     */
    private function createMezonUserData(string $username, ?string $displayName = null): string
    {
        return json_encode([
            'id' => rand(100000, 999999),
            'username' => $username,
            'display_name' => $displayName ?? $username,
            'language_code' => 'vi'
        ]);
    }

    //Test cases

    public function testMezonLoginByHashSuccess(ApiTester $I)
    {
        $I->wantTo('Login successfully with valid hash data');

        $username = 'test_success_' . time();
        $userData = $this->createMezonUserData($username, 'Kyler Nguyen');

        // Create valid hash data
        $hashData = $this->createMezonHashData([
            'auth_date' => time(),
            'query_id'  => 'AAEAAAD...',
            'user'      => $userData,
        ]);

        // Send request
        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        // Check Response
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'token_type' => 'Bearer'
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.access_token');

        // Check Database to see if user has been created
        $I->seeRecord('users', [
            'username' => $username,
            'first_name' => 'Kyler Nguyen',
            'activated' => 1
        ]);
    }

    public function testMezonLoginByHashExpired(ApiTester $I)
    {
        $I->wantTo('Login failed when auth_date expired (> 5 minutes)');

        $username = 'test_expired_' . time();
        $userData = $this->createMezonUserData($username);

        // Simulate past time (current time - 301 seconds)
        $expiredTime = time() - (self::AUTH_TIMEOUT_SECONDS + 10);

        $hashData = $this->createMezonHashData([
            'auth_date' => $expiredTime,
            'user'      => $userData,
        ]);

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED); // 401
        $I->seeResponseContainsJson(['message' => 'Authentication expired']);
    }

    public function testMezonLoginByHashInvalidSignature(ApiTester $I)
    {
        $I->wantTo('Login failed when hash signature is incorrect');

        $userData = $this->createMezonUserData('hacker_user');

        // Pass second parameter as incorrect hash signature
        $hashData = $this->createMezonHashData(
            [
                'auth_date' => time(),
                'user'      => $userData,
            ],
            'invalid_signature_hash_123456789' 
        );

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED); // 401
        $I->seeResponseContainsJson(['message' => 'Authentication failed']);
    }

    public function testMezonLoginByHashDisabledUser(ApiTester $I)
    {
        $I->wantTo('Login failed when User is disabled (activated = 0)');

        $username = 'banned_user_' . time();
        
        // Create user in DB and set activated = 0
        $user = User::create([
            'username' => $username,
            'email' => $username . '@ncc.asia',
            'password' => bcrypt('123456'),
            'activated' => 0,
            'first_name' => 'Banned User'
        ]);

        $userData = $this->createMezonUserData($username);
        $hashData = $this->createMezonHashData([
            'auth_date' => time(),
            'user'      => $userData,
        ]);

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        $I->seeResponseCodeIs(HttpCode::FORBIDDEN); // 403
        $I->seeResponseContainsJson(['message' => 'User is disabled']);
    }
    
    public function testMezonLoginValidationFail(ApiTester $I) 
    {
        $I->wantTo('Login failed if hashData is not sent');
        
        $I->sendPOST('/auth/mezon-login-by-hash', []);
        
        $I->seeResponseCodeIs(HttpCode::UNPROCESSABLE_ENTITY); // 422 Laravel Validation Error
    }

    public function testMezonLoginWithExistingUser(ApiTester $I)
    {
        $I->wantTo('Login successfully with existing user');

        $username = 'existing_user';
        // Create existing user in DB
        $user = User::factory()->create([
            'username' => $username,
            'email' => $username . '@ncc.asia',
            'activated' => 1
        ]);

        // Create payload login with username same as existing user
        $userData = $this->createMezonUserData($username);
        $hashData = $this->createMezonHashData([
            'auth_date' => time(),
            'user'      => $userData,
        ]);

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeNumRecords(1, 'users', ['username' => $username]); 
    }

    public function testMezonLoginMissingHashDelimiter(ApiTester $I)
    {
        $I->wantTo('Login failed when hashData does not contain &hash=');

        // Create a valid base64 string but not in the correct Mezon structure
        $invalidPayload = base64_encode('auth_date=123&user=abc');

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $invalidPayload,
        ]);

        // Controller returns 401 at the strpos === false check
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseContainsJson(['message' => 'Authentication failed']);
    }

    public function testMezonLoginWithCorruptedUserJson(ApiTester $I)
    {
        $I->wantTo('Login failed when hash is correct but User JSON is corrupted');

        // Simulate params with 'user' being a non-JSON string
        $hashData = $this->createMezonHashData([
            'auth_date' => time(),
            'user'      => '{bad_json_format: true', // Missing closing bracket or key without quotes
        ]);

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        // Catch block -> return 500
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
        $I->seeResponseContainsJson(['message' => 'Login error']);
    }

    public function testMezonLoginMissingUsernameInJson(ApiTester $I)
    {
        $I->wantTo('Login failed when User JSON is missing username');

        // Valid JSON but missing 'username' key
        $badUserData = json_encode([
            'id' => 12345,
            'display_name' => 'No Name'
        ]);

        $hashData = $this->createMezonHashData([
            'auth_date' => time(),
            'user'      => $badUserData,
        ]);

        $I->sendPOST('/auth/mezon-login-by-hash', [
            'hashData' => $hashData,
        ]);

        // Exception "Invalid user JSON" -> Catch -> 500
        $I->seeResponseCodeIs(HttpCode::INTERNAL_SERVER_ERROR);
        $I->seeResponseContainsJson(['message' => 'Login error']);
    }
}