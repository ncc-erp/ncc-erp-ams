<?php
namespace App\Http\Controllers\Api\Webhooks;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Location;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\HRMUserHookRequest;


class HRMUserHookController extends Controller
{
    private const USER_CREATED                   = 'created';
    private const USER_UPDATED                   = 'updated';
    private const USER_QUIT_CONFIRMED            = 'quit_confirmed';
    private const USER_PAUSE_CONFIRMED           = 'pause_confirmed';
    private const USER_MATERNITY_LEAVE_CONFIRMED = 'maternity_leave_confirmed';
    private const USER_BACK_TO_WORK_CONFIRMED    = 'back_to_work_confirmed';


    //  Create User By HRM
    public function createUserByHRM(HRMUserHookRequest $request): JsonResponse
    {
        $user = $this->findUserByEmail($request['emailAddress']);

        if ($user) {
            return $this->errorResponse("User already exists", new Exception('User already exists', Response::HTTP_BAD_REQUEST));
        }

        $mappedData = $this->mappingUser($request->all());
        $user = User::create($mappedData);
        
        return $this->successResponse($user->toArray(), 'User created successfully', Response::HTTP_CREATED);
    }

      //  Update User By HRM
      public function updateUserByHRM(HRMUserHookRequest $request): JsonResponse
      {
            $user = $this->findUserByEmail($request['emailAddress']);
    
            if (!$user) {
                return $this->errorResponse("User not found", new Exception('User not found', Response::HTTP_BAD_REQUEST));
            }

            $mappedData = $this->mappingUser($request->all());
            $user->update($mappedData);

            return $this->successResponse($user->toArray(), 'User updated successfully');
      }

    //  Confirm User Quit
    public function confirmUserQuit(HRMUserHookRequest $request): JsonResponse
    {
        return $this->confirmUserStatus($request, self::USER_QUIT_CONFIRMED, 'quit');
    }

    //  Confirm User Pause
    public function confirmUserPause(HRMUserHookRequest $request): JsonResponse
    {
        return $this->confirmUserStatus($request, self::USER_PAUSE_CONFIRMED, 'pause');
    }

    //  Confirm User Maternity Leave
    public function confirmUserMaternityLeave(HRMUserHookRequest $request): JsonResponse
    {
        return $this->confirmUserStatus($request, self::USER_MATERNITY_LEAVE_CONFIRMED, 'maternity leave');
    }

    //  Confirm User Back to Work
    public function confirmUserBackToWork(HRMUserHookRequest $request): JsonResponse
    {
        return $this->confirmUserStatus($request, self::USER_BACK_TO_WORK_CONFIRMED, 'back to work');
    }

    //  Confirm User Status
    private function confirmUserStatus(HRMUserHookRequest $request, string $status, string $actionType): JsonResponse
    {
        $user = $this->findUserByEmail($request['emailAddress']);

        if (!$user) {
            return $this->errorResponse("User not found", new Exception('User not found', Response::HTTP_BAD_REQUEST));
        }


        $activatedStatus = ($status === self::USER_BACK_TO_WORK_CONFIRMED || $status === self::USER_MATERNITY_LEAVE_CONFIRMED) ? 1 : 0;
    
        $updatedNotes = $this->updateUserStatusInNotes($user, $status, $request['dateAt']);
        
        $user->update([
            'activated' => $activatedStatus,
            'notes' => $updatedNotes,
        ]);

        return $this->successResponse($user->toArray(), "User {$actionType} status confirmed successfully");
    }

    //  Check Connect
    public function checkConnect(): JsonResponse
    {
        return $this->successResponse([], 'Connected successfully');
    }

    //  Service:  Update User Status In Notes
    private function updateUserStatusInNotes(User $user, string $status, string $dateAt): string
    {
        $statusLabels = [
            self::USER_QUIT_CONFIRMED => 'Quit',
            self::USER_PAUSE_CONFIRMED => 'Paused',
            self::USER_MATERNITY_LEAVE_CONFIRMED => 'Maternity Leave',
            self::USER_BACK_TO_WORK_CONFIRMED => 'Back to Work'
        ];
        
        $statusLabel = $statusLabels[$status] ?? $status;
        $statusInfo = "Status: {$statusLabel} at {$dateAt}";
        
        $existingNotes = $user->notes ?? '';
        
        if (empty($existingNotes)) {
            return $statusInfo;
        }
        
        if (strpos($existingNotes, $statusInfo) !== false) {
            return $existingNotes;
        }
        
        return $existingNotes . ' | ' . $statusInfo;
    }

    // Success Response
    private function successResponse(array $payload, string $message, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse('success', $payload, $message),
            $statusCode
        );
    }

    // Error Response
    private function errorResponse(string $userMessage, Exception $e): JsonResponse
    {
        return response()->json(
            Helper::formatStandardApiResponse($e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR, null, $userMessage),
            $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    // Find Location ID by Branch Code
    private function findLocationIdByBranchCode(?string $branchCode): ?int
    {
        if (empty($branchCode)) {
            return null;
        }
        
        $location = Location::where('branch_code', $branchCode)->first();
        return $location ? $location->id : null;
    }


    // Find User By Email
    private function findUserByEmail(string $email) {
        $user = User::where('email', $email)->first();
        return $user;
    }

    // Mapping User
    private function mappingUser(array $data): array
    {
        $nameParts = explode(' ', trim($data['fullName']));
        $firstName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 0, -1)) : $data['fullName'];
        $lastName = count($nameParts) > 1 ? end($nameParts) : '';

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $data['emailAddress'],
            'username' => $this->generateUsername($data['emailAddress']),
            'job_position_code' => $data['positionCode'] ?? null,
            'user_type' => $data['type'] ?? null,
            'location_id' => $this->findLocationIdByBranchCode($data['branchCode'] ?? null),
            "mezon_id" => $data['mezonId'] ?? null,
        ];
    }

 
    // Generate Username
    private function generateUsername(string $email): string
    {
        return substr($email, 0, strpos($email, '@'));
    }
}
 