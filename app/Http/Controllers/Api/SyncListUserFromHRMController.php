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
use Illuminate\Support\Facades\DB;

class SyncListUserFromHRMController extends Controller
{
    private const DEFAULT_USER_PERMISSIONS = '{"superuser":"0","admin":"0","self.two_factor":"1","self.checkout_assets":"1"}';
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
            $createdEmails = [];
            $updatedEmails = [];
            $skipEmails = [];

            foreach ($hrmUsers as $hrmUser) {
                $this->processUser($hrmUser, $locations, $syncStats, $createdEmails, $updatedEmails, $skipEmails);
            }
            \Log::info('HRM sync created_emails', ['created_emails' => $createdEmails]);
            \Log::info('HRM sync updated_emails', ['updated_emails' => $updatedEmails]);
            \Log::info('HRM sync skip_emails', ['skip_emails' => $skipEmails]);

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
            ],
            'verify' => false, 
            'timeout' => 30
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
    private function processUser(array $hrmUser, Collection &$locations, array &$syncStats, array &$createdEmails, array &$updatedEmails, array &$skipEmails): void
    {
        $syncStats['processed']++;

        if (!$this->isValidHrmUser($hrmUser) || !$this->isValidEmail($hrmUser['email'] ?? '')) {
            $syncStats['skipped']++;
            // collect skip email (if available) for final log
            if (!empty($hrmUser['email'])) {
                $skipEmails[] = $hrmUser['email'];
            }
            return;
        }

        $username = $this->extractUsername($hrmUser['email']);
        $user = $this->findOrCreateUser($username, $syncStats);
        
        $isNew = !$user->exists;

        $this->updateUserData($user, $hrmUser, $locations);
        $user->save();

        if ($isNew) {
            // collect only email for final single log
            $createdEmails[] = $user->email;
        } else {
            // collect email for updated list (keeps existing behavior of always saving)
            $updatedEmails[] = $user->email;
        }
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

    //  Get full name 
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

        $findLocation = Location::where('branch_code', $branchCode)->first();

        
        if ($findLocation) {
            return $findLocation->id;
        }
        
        // Create new location when not exists
        $newLocation = $this->createNewLocation($branchCode);
        
        if (!$newLocation || !$newLocation->id) {
            throw new Exception("Failed to create location for branch code: $branchCode");
        }
        
        $locations->push($newLocation);
        
        return $newLocation->id;
    }

    //  Create new location
    private function createNewLocation(string $branchCode): Location
    {
        $location = new Location();
        $location->name = $branchCode;
        $location->branch_code = $branchCode;
        $location->save();
        
        return $location;
    }

    private function successResponse(array $syncStats): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse('success', $syncStats, 'User sync completed successfully')
        );
    }

    private function errorResponse(string $message): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse('error', null, 'User sync failed: ' . $message)
        );
    }
}
