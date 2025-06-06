<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SyncListUserFromHRMController extends Controller
{
    private const DEFAULT_USER_PERMISSIONS = '{"superuser":"0","admin":"0","self.two_factor":"1","self.checkout_assets":"1"}';
    private const LOCATION_NAME_PREFIX = 'NCC ';
    private const EMAIL_PARTS_COUNT = 2;

    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    //  Sync users from HRM 
    public function syncListUser(Request $request): JsonResponse
    {
        try {
            $hrmUsers = $this->fetchUsersFromHRM();
            $locations = $this->getLocationsCollection();
            $syncStats = $this->initializeSyncStats();

            foreach ($hrmUsers as $hrmUser) {
                $this->processUser($hrmUser, $locations, $syncStats);
            }

            return $this->successResponse($syncStats);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    //  Fetch users from HRM 
    private function fetchUsersFromHRM(): array
    {
        $response = $this->client->get(env('HRM_API'), [
            'headers' => [
                'X-Secret-Key' => env('HRM_SECRET_KEY')
            ]
        ]);

        $responseData = json_decode($response->getBody(), true);

        if (!$this->isValidHrmResponse($responseData)) {
            throw new Exception('Invalid response from HRM API');
        }

        return $responseData['result'];
    }

    //  Validate HRM API response
    private function isValidHrmResponse(?array $responseData): bool
    {
        return $responseData && 
               isset($responseData['result']) && 
               is_array($responseData['result']);
    }

    //  Get locations collection
    private function getLocationsCollection(): Collection
    {
        return Location::select(['id', 'branch_code'])->get();
    }

    //  Initialize sync statistics
    private function initializeSyncStats(): array
    {
        return [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0
        ];
    }

    //  Process a single user from HRM
    private function processUser(array $hrmUser, Collection &$locations, array &$syncStats): void
    {
        $syncStats['processed']++;

        if (!$this->isValidHrmUser($hrmUser) || !$this->isValidEmail($hrmUser['email'])) {
            $syncStats['skipped']++;
            return;
        }

        $username = $this->extractUsername($hrmUser['email']);
        $user = $this->findOrCreateUser($username, $syncStats);
        
        $this->updateUserData($user, $hrmUser, $locations);
        $user->save();
    }

    //  Validate HRM user data
    private function isValidHrmUser(array $hrmUser): bool
    {
        return isset($hrmUser['email']) && isset($hrmUser['fullName']);
    }

    //  Validate email format and domain
    private function isValidEmail(string $email): bool
    {
        if (!Str::contains($email, '@')) {
            return false;
        }

        $emailParts = explode('@', $email);
        
        return count($emailParts) === self::EMAIL_PARTS_COUNT && 
               $emailParts[1] === env('MAIL_DOMAIN');
    }

    //  Extract username from email
    private function extractUsername(string $email): string
    {
        return explode('@', $email)[0];
    }

    //  Find existing user or create new one
    private function findOrCreateUser(string $username, array &$syncStats): User
    {
        $user = User::where('username', $username)->first();

        if (!$user) {
            $user = $this->createNewUser($username);
            $syncStats['created']++;
        } else {
            $syncStats['updated']++;
        }

        return $user;
    }

    //  Create new user with default settings
    private function createNewUser(string $username): User
    {
        $user = new User();
        $user->username = $username;
        $user->activated = true;
        $user->permissions = self::DEFAULT_USER_PERMISSIONS;

        return $user;
    }

    //  Update user data from HRM
    private function updateUserData(User $user, array $hrmUser, Collection &$locations): void
    {
        $fullName = $this->parseFullName($hrmUser['fullName']);

        $user->first_name = $fullName['first_name'];
        $user->last_name = $fullName['last_name'];
        $user->email = $hrmUser['email'];
        $user->job_position_code = $hrmUser['jobPositionCode'] ?? null;
        $user->user_type = $hrmUser['userTypeName'] ?? null;
        $user->mezon_id = $hrmUser['mezonId'] ?? null;

        if (isset($hrmUser['branchCode'])) {
            $user->location_id = $this->getOrCreateLocationId($locations, $hrmUser['branchCode']);
        }
    }

    //  Parse full name into first and last name
    private function parseFullName(string $fullName): array
    {
        $nameParts = explode(' ', $fullName);
        $lastName = array_pop($nameParts);
        $firstName = implode(' ', $nameParts);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
    }

    //  Get location ID or create new location if not exists
    private function getOrCreateLocationId(Collection &$locations, string $branchCode): int
    {
        // Find existing location
        $existingLocation = $locations->firstWhere('branch_code', $branchCode);
        
        if ($existingLocation) {
            return $existingLocation->id;
        }

        // Create new location
        $newLocation = $this->createNewLocation($branchCode);
        $locations->push($newLocation);
        
        return $newLocation->id;
    }

    //  Create new location
    private function createNewLocation(string $branchCode): Location
    {
        $location = new Location();
        $location->name = self::LOCATION_NAME_PREFIX . $branchCode;
        $location->branch_code = $branchCode;
        $location->save();

        return $location;
    }

    //  Return success response
    private function successResponse(array $syncStats): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse('success', $syncStats, 'User sync completed successfully')
        );
    }

    //  Return error response
    private function errorResponse(string $message): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse('error', null, 'User sync failed: ' . $message)
        );
    }
}
