<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\User;
use Gate;
use Illuminate\Database\Eloquent\Collection;

class UsersTransformer
{
    /**
     * @OA\Schema(
     *     schema="Manager",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=2),
     *     @OA\Property(property="name", type="string", example="IT NCC Admin")
     * )
     *
     * @OA\Schema(
     *     schema="Department", 
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="IT Department")
     * )
     *
     * @OA\Schema(
     *     schema="Location",
     *     type="object", 
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Head Office")
     * )
     *
     * @OA\Schema(
     *     schema="Company",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1), 
     *     @OA\Property(property="name", type="string", example="NCC Soft")
     * )
     *
     * @OA\Schema(
     *     schema="DateObject",
     *     type="object",
     *     @OA\Property(property="date", type="string", example="2024-01-15 10:30:00"),
     *     @OA\Property(property="formatted", type="string", example="Jan 15, 2024 10:30 AM")
     * )
     *
     * @OA\Schema(
     *     schema="GroupItem",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Administrators")
     * )
     *
     * @OA\Schema(
     *     schema="Groups",
     *     type="object",
     *     @OA\Property(property="total", type="integer", example=2),
     *     @OA\Property(
     *         property="rows",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/GroupItem")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="AvailableActions",
     *     type="object",
     *     @OA\Property(property="update", type="boolean", example=true),
     *     @OA\Property(property="delete", type="boolean", example=false), 
     *     @OA\Property(property="clone", type="boolean", example=true),
     *     @OA\Property(property="restore", type="boolean", example=false)
     * )
     *
     * @OA\Schema(
     *     schema="User",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="avatar", type="string", example="https://..."),
     *     @OA\Property(property="name", type="string", example="NCC Admin"),
     *     @OA\Property(property="mezon_id", type="string", nullable=true, example="12345"),
     *     @OA\Property(property="first_name", type="string", example="NCC"),
     *     @OA\Property(property="last_name", type="string", example="Admin"), 
     *     @OA\Property(property="username", type="string", example="nccadmin"),
     *     @OA\Property(property="remote", type="boolean", example=true),
     *     @OA\Property(property="locale", type="string", nullable=true, example="en_US"),
     *     @OA\Property(property="employee_num", type="string", example="EMP001"),
     *     @OA\Property(property="manager", oneOf={@OA\Schema(ref="#/components/schemas/Manager"), @OA\Schema(type="null")}),
     *     @OA\Property(property="jobtitle", type="string", nullable=true, example="Developer"),
     *     @OA\Property(property="phone", type="string", nullable=true, example="+1234567890"),
     *     @OA\Property(property="website", type="string", nullable=true, example="https://..."),
     *     @OA\Property(property="address", type="string", nullable=true, example="123 Main St"),
     *     @OA\Property(property="city", type="string", nullable=true, example="New York"),
     *     @OA\Property(property="state", type="string", nullable=true, example="NY"),
     *     @OA\Property(property="country", type="string", nullable=true, example="US"),
     *     @OA\Property(property="zip", type="string", nullable=true, example="10001"),
     *     @OA\Property(property="social_id", type="string", nullable=true, example="12345"),
     *     @OA\Property(property="access_token_social", type="string", nullable=true, example="token123"),
     *     @OA\Property(property="platform", type="string", nullable=true, example="google"),
     *     @OA\Property(property="email", type="string", format="email", example="nccadmin@ncc.asia"),
     *     @OA\Property(property="department", oneOf={@OA\Schema(ref="#/components/schemas/Department"), @OA\Schema(type="null")}),
     *     @OA\Property(property="location", oneOf={@OA\Schema(ref="#/components/schemas/Location"), @OA\Schema(type="null")}),
     *     @OA\Property(property="notes", type="string", example="Some notes about the user"),
     *     @OA\Property(property="permissions", type="object", description="User permissions object"),
     *     @OA\Property(property="manager_location", type="object", description="JSON decoded manager location data"),
     *     @OA\Property(property="activated", type="boolean", example=true),
     *     @OA\Property(property="ldap_import", type="boolean", example=false),
     *     @OA\Property(property="two_factor_activated", type="boolean", example=true),
     *     @OA\Property(property="two_factor_enrolled", type="boolean", example=true),
     *     @OA\Property(property="assets_count", type="integer", example=5),
     *     @OA\Property(property="licenses_count", type="integer", example=3),
     *     @OA\Property(property="accessories_count", type="integer", example=2),
     *     @OA\Property(property="consumables_count", type="integer", example=1),
     *     @OA\Property(property="company", oneOf={@OA\Schema(ref="#/components/schemas/Company"), @OA\Schema(type="null")}),
     *     @OA\Property(property="created_at", ref="#/components/schemas/DateObject"),
     *     @OA\Property(property="updated_at", ref="#/components/schemas/DateObject"),
     *     @OA\Property(property="last_login", oneOf={@OA\Schema(ref="#/components/schemas/DateObject"), @OA\Schema(type="null")}),
     *     @OA\Property(property="deleted_at", oneOf={@OA\Schema(ref="#/components/schemas/DateObject"), @OA\Schema(type="null")}),
     *     @OA\Property(property="user_type", type="string", nullable=true, example="employee"),
     *     @OA\Property(property="job_position_code", type="string", nullable=true, example="DEV001"),
     *     @OA\Property(property="available_actions", ref="#/components/schemas/AvailableActions"),
     *     @OA\Property(property="groups", oneOf={@OA\Schema(ref="#/components/schemas/Groups"), @OA\Schema(type="null")})
     * )
     *
     * @OA\Schema(
     *     schema="UsersResponse",
     *     type="object", 
     *     @OA\Property(property="total", type="integer", example=150),
     *     @OA\Property(
     *         property="rows",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function transformUsers(Collection $users, $total)
    {
        $array = [];
        foreach ($users as $user) {
            $array[] = self::transformUser($user);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformUser(User $user)
    {
        $array = [
                'id' => (int) $user->id,
                'avatar' => e($user->present()->gravatar),
                'name' => e($user->first_name).' '.e($user->last_name),
                'mezon_id' => ($user->mezon_id) ? e($user->mezon_id) : null,
                'first_name' => e($user->first_name),
                'last_name' => e($user->last_name),
                'username' => e($user->username),
                'remote' => ($user->remote == '1') ? true : false,
                'locale' => ($user->locale) ? e($user->locale) : null,
                'employee_num' => e($user->employee_num),
                'manager' => ($user->manager) ? [
                    'id' => (int) $user->manager->id,
                    'name'=> e($user->manager->username),
                ] : null,
                'jobtitle' => ($user->jobtitle) ? e($user->jobtitle) : null,
                'phone' => ($user->phone) ? e($user->phone) : null,
                'website' => ($user->website) ? e($user->website) : null,
                'address' => ($user->address) ? e($user->address) : null,
                'city' => ($user->city) ? e($user->city) : null,
                'state' => ($user->state) ? e($user->state) : null,
                'country' => ($user->country) ? e($user->country) : null,
                'zip' => ($user->zip) ? e($user->zip) : null,
                'social_id' => ($user->social_id) ? e($user->social_id) : null,
                'access_token_social' => ($user->access_token_social) ? e($user->access_token_social) : null,
                'platform' => ($user->platform) ? e($user->platform) : null,
                'email' => e($user->email),
                'department' => ($user->department) ? [
                    'id' => (int) $user->department->id,
                    'name'=> e($user->department->name),
                ] : null,
                'location' => ($user->userloc) ? [
                    'id' => (int) $user->userloc->id,
                    'name'=> e($user->userloc->name),
                ] : null,
                'notes'=> e($user->notes),
                'permissions' => $user->decodePermissions(),
                'manager_location' => json_decode($user->manager_location, true),
                'activated' => ($user->activated == '1') ? true : false,
                'ldap_import' => ($user->ldap_import == '1') ? true : false,
                'two_factor_activated' => ($user->two_factor_active()) ? true : false,
                'two_factor_enrolled' => ($user->two_factor_active_and_enrolled()) ? true : false,
                'assets_count' => (int) $user->assets_count,
                'licenses_count' => (int) $user->licenses_count,
                'accessories_count' => (int) $user->accessories_count,
                'consumables_count' => (int) $user->consumables_count,
                'company' => ($user->company) ? ['id' => (int) $user->company->id, 'name'=> e($user->company->name)] : null,
                'created_at' => Helper::getFormattedDateObject($user->created_at, 'datetime'),
                'updated_at' => Helper::getFormattedDateObject($user->updated_at, 'datetime'),
                'last_login' => Helper::getFormattedDateObject($user->last_login, 'datetime'),
                'deleted_at' => ($user->deleted_at) ? Helper::getFormattedDateObject($user->deleted_at, 'datetime') : null,
                'user_type' => ($user->user_type) ? e($user->user_type) : null,
                'job_position_code' => ($user->job_position_code) ?  e($user->job_position_code) : null,
            ];

        $permissions_array['available_actions'] = [
            'update' => (Gate::allows('update', User::class) && ($user->deleted_at == '')),
            'delete' => (Gate::allows('delete', User::class) && ($user->assets_count == 0) && ($user->licenses_count == 0) && ($user->accessories_count == 0) && ($user->consumables_count == 0)),
            'clone' => (Gate::allows('create', User::class) && ($user->deleted_at == '')),
            'restore' => (Gate::allows('create', User::class) && ($user->deleted_at != '')),
        ];

        $array += $permissions_array;

        $numGroups = $user->groups->count();
        if ($numGroups > 0) {
            $groups['total'] = $numGroups;
            foreach ($user->groups as $group) {
                $groups['rows'][] = [
                    'id' => (int) $group->id,
                    'name' => e($group->name),
                ];
            }
            $array['groups'] = $groups;
        } else {
            $array['groups'] = null;
        }

        return $array;
    }

    public function transformUsersDatatable($users)
    {
        return (new DatatablesTransformer)->transformDatatables($users);
    }
}
