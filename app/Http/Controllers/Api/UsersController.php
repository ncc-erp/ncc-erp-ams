<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveUserRequest;
use App\Http\Transformers\AccessoriesTransformer;
use App\Http\Transformers\AssetsTransformer;
use App\Http\Transformers\ConsumablesTransformer;
use App\Http\Transformers\DatatablesTransformer;
use App\Http\Transformers\LicensesTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Http\Transformers\UsersTransformer;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;

class UsersController extends Controller
{
    /**
     * @OA\Schema(
     *     schema="GoogleProfileRequest",
     *     type="object",
     *     required={"profile_obj", "client_secret"},
     *     @OA\Property(
     *         property="profile_obj",
     *         type="object",
     *         required={"email", "googleId"},
     *         @OA\Property(property="email", type="string", format="email", example="user@ncc.asia"),
     *         @OA\Property(property="googleId", type="string", example="1234567890123456789"),
     *         @OA\Property(property="name", type="string", example="John Doe"),
     *         @OA\Property(property="givenName", type="string", example="John"),
     *         @OA\Property(property="familyName", type="string", example="Doe"),
     *         @OA\Property(property="imageUrl", type="string", format="uri", example="https://lh3.googleusercontent.com/...")
     *     ),
     *     @OA\Property(
     *         property="client_secret",
     *         type="object",
     *         required={"access_token"},
     *         @OA\Property(property="access_token", type="string", example="ya29.a0ARrdaM..."),
     *         @OA\Property(property="token_type", type="string", example="Bearer"),
     *         @OA\Property(property="expires_in", type="integer", example=3599),
     *         @OA\Property(property="scope", type="string", example="openid email profile")
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="BasicLoginRequest",
     *     type="object",
     *     required={"username", "password"},
     *     @OA\Property(property="username", type="string", example="nccadmin"),
     *     @OA\Property(property="password", type="string", format="password", example="123456a@")
     * )
     *
     * @OA\Schema(
     *     schema="AuthenticationResponse",
     *     type="object",
     *     @OA\Property(property="token_type", type="string", example="Bearer"),
     *     @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...")
     * )
     *
     * @OA\Schema(
     *     schema="UnauthorizedResponse",
     *     type="object",
     *     @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     *
     * @OA\Schema(
     *     schema="ValidationErrorResponse",
     *     type="object",
     *     @OA\Property(property="message", type="string", example="The given data was invalid."),
     *     @OA\Property(
     *         property="errors",
     *         type="object",
     *         @OA\Property(
     *             property="field_name",
     *             type="array",
     *             @OA\Items(type="string", example="The field name is required.")
     *         )
     *     )
     * )
     *
     * @OA\Schema(
     *     schema="ErrorResponse",
     *     type="object", 
     *     @OA\Property(property="message", type="string", example="User not found")
     * )
     *
     * @OA\Schema(
     *     schema="MezonOAuthRequest", 
     *     type="object",
     *     required={"code", "state"},
     *     @OA\Property(property="code", type="string", example="auth_code_from_mezon"),
     *     @OA\Property(property="state", type="string", example="base64_encoded_state_string")
     * )
     *
     * @OA\Schema(
     *     schema="MezonHashLoginRequest",
     *     type="object", 
     *     required={"dataCheck", "hashKey", "userEmail", "userName"},
     *     @OA\Property(property="dataCheck", type="string", example="user_data_string"),
     *     @OA\Property(property="hashKey", type="string", example="hmac_hash_verification_code"),
     *     @OA\Property(property="userEmail", type="string", format="email", example="john.doe@ncc.asia"),
     *     @OA\Property(property="userName", type="string", example="john.doe")
     * )
     *
     * @OA\Schema(
     *     schema="MezonAuthUrlResponse",
     *     type="object",
     *     @OA\Property(property="url", type="string", format="uri", example="https://chat.ncc.asia/oauth2/auth?client_id=ncc_ams&redirect_uri=https://ams.ncc.asia/auth/callback&response_type=code&scope=openid%20offline&state=abcdef123456")
     * )
     */
    
    public function index(Request $request)
    {
        $this->authorize('view', User::class);

        $users = User::select([
            'users.activated',
            'users.address',
            'users.avatar',
            'users.city',
            'users.mezon_id',
            'users.company_id',
            'users.country',
            'users.created_at',
            'users.deleted_at',
            'users.department_id',
            'users.email',
            'users.employee_num',
            'users.first_name',
            'users.id',
            'users.jobtitle',
            'users.last_login',
            'users.last_name',
            'users.locale',
            'users.location_id',
            'users.manager_id',
            'users.notes',
            'users.permissions',
            'users.phone',
            'users.state',
            'users.two_factor_enrolled',
            'users.two_factor_optin',
            'users.updated_at',
            'users.username',
            'users.manager_location',
            'users.zip',
            'users.remote',
            'users.ldap_import',
            'users.user_type',
            'users.job_position_code'

        ])->with('manager', 'groups', 'userloc', 'company', 'department', 'assets', 'licenses', 'accessories', 'consumables')
            ->withCount('assets as assets_count', 'licenses as licenses_count', 'accessories as accessories_count', 'consumables as consumables_count');
        $users = Company::scopeCompanyables($users);


        if (($request->filled('deleted')) && ($request->input('deleted') == 'true')) {
            $users = $users->onlyTrashed();
        } elseif (($request->filled('all')) && ($request->input('all') == 'true')) {
            $users = $users->withTrashed();
        }

        if ($request->filled('activated')) {
            $users = $users->where('users.activated', '=', $request->input('activated'));
        }

        if ($request->filled('company_id')) {
            $users = $users->where('users.company_id', '=', $request->input('company_id'));
        }

        if ($request->filled('location')) {
            $users = $users->whereIn('users.location_id', $request->input('location'));
        }

        if ($request->filled('email')) {
            $users = $users->where('users.email', '=', $request->input('email'));
        }

        if ($request->filled('username')) {
            $users = $users->where('users.username', '=', $request->input('username'));
        }

        if ($request->filled('first_name')) {
            $users = $users->where('users.first_name', '=', $request->input('first_name'));
        }

        if ($request->filled('last_name')) {
            $users = $users->where('users.last_name', '=', $request->input('last_name'));
        }

        if ($request->filled('employee_num')) {
            $users = $users->where('users.employee_num', '=', $request->input('employee_num'));
        }

        if ($request->filled('state')) {
            $users = $users->where('users.state', '=', $request->input('state'));
        }

        if ($request->filled('country')) {
            $users = $users->where('users.country', '=', $request->input('country'));
        }

        if ($request->filled('zip')) {
            $users = $users->where('users.zip', '=', $request->input('zip'));
        }

        if ($request->filled('group_id')) {
            $users = $users->ByGroup($request->get('group_id'));
        }

        if ($request->filled('department_id')) {
            $users = $users->where('users.department_id', '=', $request->input('department_id'));
        }

        if ($request->filled('manager_id')) {
            $users = $users->where('users.manager_id','=',$request->input('manager_id'));
        }

        if ($request->filled('ldap_import')) {
            $users = $users->where('ldap_import', '=', $request->input('ldap_import'));
        }

        if ($request->filled('remote')) {
            $users = $users->where('remote', '=', $request->input('remote'));
        }

        if($request->filled('user_type')) {
            $users = $users->whereIn('user_type', $request->input('user_type'));
        }

        if($request->filled('job_position_code')) {
            $users = $users->whereIn('job_position_code', $request->input('job_position_code'));
        }

        if ($request->filled('assets_count')) {
            $users->has('assets', '=', $request->input('assets_count'));
        }

        if ($request->filled('consumables_count')) {
            $users->has('consumables', '=', $request->input('consumables_count'));
        }

        if ($request->filled('licenses_count')) {
            $users->has('licenses', '=', $request->input('licenses_count'));
        }

        if ($request->filled('accessories_count')) {
            $users->has('accessories', '=', $request->input('accessories_count'));
        }

        if ($request->filled('search')) {
            $users = $users->TextSearch($request->input('search'));
        }

        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $offset = (($users) && (request('offset') > $users->count())) ? 0 : request('offset', 0);

        // Set the offset to the API call's offset, unless the offset is higher than the actual count of items in which
        // case we override with the actual count, so we should return 0 items.
        $offset = (($users) && ($request->get('offset') > $users->count())) ? $users->count() : $request->get('offset', 0);

        // Check to make sure the limit is not higher than the max allowed
        ((config('app.max_results') >= $request->input('limit')) && ($request->filled('limit'))) ? $limit = $request->input('limit') : $limit = config('app.max_results');


        switch ($request->input('sort')) {
            case 'manager':
                $users = $users->OrderManager($order);
                break;
            case 'location':
                $users = $users->OrderLocation($order);
                break;
            case 'department':
                $users = $users->OrderDepartment($order);
                break;
            case 'company':
                $users = $users->OrderCompany($order);
                break;
            default:
                $allowed_columns =
                    [
                        'last_name', 'first_name', 'email', 'jobtitle', 'username', 'employee_num',
                        'assets', 'accessories', 'consumables', 'licenses', 'groups', 'activated', 'created_at',
                        'two_factor_enrolled', 'two_factor_optin', 'last_login', 'assets_count', 'licenses_count',
                        'consumables_count', 'accessories_count', 'phone', 'address', 'city', 'state',
                        'country', 'zip', 'id', 'ldap_import', 'remote', 'mezon_id'
                    ];

                $sort = in_array($request->get('sort'), $allowed_columns) ? $request->get('sort') : 'first_name';
                $users = $users->orderBy($sort, $order);
                break;
        }

        $total = $users->count();
        $users = $users->skip($offset)->take($limit)->get();

        return (new UsersTransformer)->transformUsers($users, $total);
    }

    /**
     * Gets a paginated collection for the select2 menus
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0.16]
     * @see \App\Http\Transformers\SelectlistTransformer
     */
    /**
     * @OA\Get(
     *     path="/api/v1/users/selectlist",
     *     tags={"Users"},
     *     summary="Get a list of users for dropdown menus",
     *     description="Returns a paginated list of users formatted for select2 dropdown menus",
     *     operationId="getUsersSelectList",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for filtering results",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of results to return per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer", example=50),
     *             @OA\Property(
     *                 property="rows", 
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="text", type="string", example="Admin User")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function selectlist(Request $request)
    {
        $users = User::select(
            [
                'users.id',
                'users.username',
                'users.employee_num',
                'users.first_name',
                'users.last_name',
                'users.gravatar',
                'users.avatar',
                'users.email',
            ]
        )->where('show_in_list', '=', '1');

        $users = Company::scopeCompanyables($users);

        if ($request->filled('location_id')) {
            $users = $users->where('location_id', '=', $request->get('location_id'));
        }

        if ($request->filled('search')) {
            $users = $users->SimpleNameSearch($request->get('search'))
                    ->orWhere('username', 'LIKE', '%'.$request->get('search').'%')
                    ->orWhere('employee_num', 'LIKE', '%'.$request->get('search').'%');
        }
        
        $users = $users->orderBy('last_name', 'asc')->orderBy('first_name', 'asc');
        $users = $users->paginate(800);

        foreach ($users as $user) {
            $name_str = '';
            if ($user->last_name != '') {
                $name_str .= $user->last_name.', ';
            }
            $name_str .= $user->first_name;

            if ($user->username != '') {
                $name_str .= ' ('.$user->username.')';
            }

            if ($user->employee_num != '') {
                $name_str .= ' - #'.$user->employee_num;
            }

            $user->use_text = $name_str;
            $user->use_image = ($user->present()->gravatar) ? $user->present()->gravatar : null;
        }

        return (new SelectlistTransformer)->transformSelectlist($users);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     description="Creates a new user and returns their details",
     *     operationId="createUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "username", "password", "password_confirmation"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="employee_num", type="string", example="EMP002"),
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="manager_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Some notes about this user"),
     *             @OA\Property(property="activated", type="boolean", example=true),
     *             @OA\Property(property="permissions", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="messages", type="string", example="User created successfully"),
     *             @OA\Property(property="payload", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="username", type="array", @OA\Items(type="string", example="The username field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function store(SaveUserRequest $request)
    {
        $this->authorize('create', User::class);

        $user = new User;
        $user->fill($request->all());

        if ($request->has('permissions')) {
            $permissions_array = $request->input('permissions');

            // Strip out the superuser permission if the API user isn't a superadmin
            if (! Auth::user()->isSuperUser()) {
                unset($permissions_array['superuser']);
            }

            // Return error if user is branchadmin but haven't manager_location
            $permissions = json_decode($permissions_array, true);
            $manager_location = json_decode($request->input('manager_location'), true);
            if (
                isset($permissions['branchadmin']) &&
                $permissions['branchadmin'] == config('enum.permission_status.ALLOW') &&
                empty($manager_location)
            ) {
                $error['manager_location'] = trans('admin/users/message.manager_location');
                return response()->json(Helper::formatStandardApiResponse('error', null, $error));
            }

            $user->permissions = $permissions_array;
        }

        $tmp_pass = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 20);
        $user->password = bcrypt($request->get('password', $tmp_pass));

        app('App\Http\Requests\ImageUploadRequest')->handleImages($user, 600, 'image', 'avatars', 'avatar');

        if ($user->save()) {
            if ($request->filled('groups')) {
                $user->groups()->sync($request->input('groups'));
            } else {
                $user->groups()->sync([]);
            }

            return response()->json(Helper::formatStandardApiResponse('success', (new UsersTransformer)->transformUser($user), trans('admin/users/message.success.create')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $user->getErrors()), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Display the specified resource.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get specific user details",
     *     description="Returns detailed information about a specific user",
     *     operationId="getUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to get",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="first_name", type="string", example="Admin"),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="employee_num", type="string", example="EMP001"),
     *             @OA\Property(property="company", type="object"),
     *             @OA\Property(property="department", type="object"),
     *             @OA\Property(property="location", type="object"),
     *             @OA\Property(property="manager", type="object"),
     *             @OA\Property(property="groups", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="assets_count", type="integer", example=5),
     *             @OA\Property(property="licenses_count", type="integer", example=3),
     *             @OA\Property(property="accessories_count", type="integer", example=2),
     *             @OA\Property(property="consumables_count", type="integer", example=4),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $this->authorize('view', User::class);
        $user = User::withCount('assets as assets_count', 'licenses as licenses_count', 'accessories as accessories_count', 'consumables as consumables_count')->findOrFail($id);

        return (new UsersTransformer)->transformUser($user);
    }


    /**
     * Update the specified resource in storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update a user",
     *     description="Updates an existing user and returns their updated details",
     *     operationId="updateUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="username", type="string", example="johndoe"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="employee_num", type="string", example="EMP002"),
     *             @OA\Property(property="company_id", type="integer", example=1),
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="manager_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Some notes about this user"),
     *             @OA\Property(property="activated", type="boolean", example=true),
     *             @OA\Property(property="permissions", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="messages", type="string", example="User updated successfully"),
     *             @OA\Property(property="payload", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="username", type="array", @OA\Items(type="string", example="The username has already been taken."))
     *             )
     *         )
     *     )
     * )
     */
    public function update(SaveUserRequest $request, $id)
    {
        $this->authorize('update', User::class);

        $user = User::findOrFail($id);

        /**
         * This is a janky hack to prevent people from changing admin demo user data on the public demo.
         * 
         * The $ids 1 and 2 are special since they are seeded as superadmins in the demo seeder.
         * 
         *  Thanks, jerks. You are why we can't have nice things. - snipe
         * 
         */


        if ((($id == 1) || ($id == 2)) && (config('app.lock_passwords'))) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'Permission denied. You cannot update user information via API on the demo.'));
        }


        $user->fill($request->all());

        if ($user->id == $request->input('manager_id')) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'You cannot be your own manager'));
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->input('password'));
        }

        // We need to use has()  instead of filled()
        // here because we need to overwrite permissions
        // if someone needs to null them out
        if ($request->has('permissions')) {
            $permissions_array = $request->input('permissions');

            // Strip out the superuser permission if the API user isn't a superadmin
            if (! Auth::user()->isSuperUser()) {
                unset($permissions_array['superuser']);
            }
            
            $permissions = json_decode($permissions_array, true);
            if (isset($permissions['branchadmin'])) {

                //Delete list manager_location if User isn't a branchadmin
                if ($permissions['branchadmin'] != config('enum.permission_status.ALLOW')) {
                    $user->manager_location = null;
                }
                
                //Return error if user is branchadmin but haven't manager_location
                $manager_location = json_decode($request->input('manager_location'), true);
                if ($permissions['branchadmin'] == config('enum.permission_status.ALLOW') && empty($manager_location)) {
                    $error['manager_location'] = trans('admin/users/message.manager_location');
                    return response()->json(Helper::formatStandardApiResponse('error', null, $error));
                }
            }

            $user->permissions = $permissions_array;
        }



        // Update the location of any assets checked out to this user
        Asset::where('assigned_type', User::class)
            ->where('assigned_to', $user->id)->update(['location_id' => $request->input('location_id', null)]);


        app('App\Http\Requests\ImageUploadRequest')->handleImages($user, 600, 'image', 'avatars', 'avatar');

        if ($user->save()) {

            // Sync group memberships:
            // This was changed in Snipe-IT v4.6.x to 4.7, since we upgraded to Laravel 5.5
            // which changes the behavior of has vs filled.
            // The $request->has method will now return true even if the input value is an empty string or null.
            // A new $request->filled method has was added that provides the previous behavior of the has method.

            // Check if the request has groups passed and has a value
            if ($request->filled('groups')) {
                $user->groups()->sync($request->input('groups'));
                // The groups field has been passed but it is null, so we should blank it out
            } elseif ($request->has('groups')) {
                $user->groups()->sync([]);
            }


            return response()->json(Helper::formatStandardApiResponse('success', (new UsersTransformer)->transformUser($user), trans('admin/users/message.success.update')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $user->getErrors()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete a user",
     *     description="Deletes a specific user",
     *     operationId="deleteUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of user to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="messages", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User cannot be deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="messages", type="string", example="This user still has assets assigned to them")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $this->authorize('delete', User::class);
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if (($user->assets) && ($user->assets->count() > 0)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/users/message.error.delete_has_assets')));
        }

        if (($user->licenses) && ($user->licenses->count() > 0)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'This user still has '.$user->licenses->count().' license(s) associated with them and cannot be deleted.'));
        }

        if (($user->accessories) && ($user->accessories->count() > 0)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'This user still has '.$user->accessories->count().' accessories associated with them.'));
        }

        if (($user->managedLocations()) && ($user->managedLocations()->count() > 0)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, 'This user still has '.$user->managedLocations()->count().' locations that they manage.'));
        }

        if ($user->delete()) {

            // Remove the user's avatar if they have one
            if (Storage::disk('public')->exists('avatars/'.$user->avatar)) {
                try {
                    Storage::disk('public')->delete('avatars/'.$user->avatar);
                } catch (\Exception $e) {
                    \Log::debug($e);
                }
            }

            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/users/message.success.delete')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/users/message.error.delete')));
    }

    /**
     * Return JSON containing a list of assets assigned to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @param $userId
     * @return string JSON
     * 
     * @OA\Get(
     *     path="/api/v1/users/{user}/assets",
     *     tags={"Users"},
     *     summary="List assets assigned to a user",
     *     description="Returns a list of assets assigned to a specified user",
     *     operationId="getUserAssets",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of user to get assets for",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer", example=2),
     *             @OA\Property(
     *                 property="rows",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="MacBook Pro 15"),
     *                     @OA\Property(property="asset_tag", type="string", example="NCC001"),
     *                     @OA\Property(property="serial", type="string", example="C02XXXXHTD57"),
     *                     @OA\Property(property="model", type="object"),
     *                     @OA\Property(property="status_label", type="object"),
     *                     @OA\Property(property="assigned_to", type="object"),
     *                     @OA\Property(property="created_at", type="object"),
     *                     @OA\Property(property="updated_at", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function assets(Request $request, $id)
    {
        $this->authorize('view', User::class);
        $this->authorize('view', Asset::class);
        $assets = Asset::where('assigned_to', '=', $id)->where('assigned_type', '=', User::class)->with('model')->get();

        return (new AssetsTransformer)->transformAssets($assets, $assets->count(), $request);
    }


    /**
     * Return JSON containing a list of consumables assigned to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @param $userId
     * @return string JSON
     * 
     * @OA\Get(
     *     path="/api/v1/users/{user}/consumables",
     *     tags={"Users"},
     *     summary="List consumables assigned to a user",
     *     description="Returns a list of consumables assigned to a specified user",
     *     operationId="getUserConsumables",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of user to get consumables for",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer", example=1),
     *             @OA\Property(
     *                 property="rows",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Printer Paper"),
     *                     @OA\Property(property="category", type="object"),
     *                     @OA\Property(property="location", type="object"),
     *                     @OA\Property(property="qty", type="integer", example=50),
     *                     @OA\Property(property="created_at", type="object"),
     *                     @OA\Property(property="updated_at", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function consumables(Request $request, $id)
    {
        $this->authorize('view', User::class);
        $this->authorize('view', Consumable::class);
        $user = User::findOrFail($id);
        $consumables = $user->consumables;
        return (new ConsumablesTransformer)->transformConsumables($consumables, $consumables->count(), $request);
    }

    /**
     * Return JSON containing a list of accessories assigned to a user.
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.6.14]
     * @param $userId
     * @return string JSON
     */
    /**
     * @OA\Get(
     *     path="/api/v1/users/{user}/accessories",
     *     tags={"Users"},
     *     summary="List accessories assigned to a user",
     *     description="Returns a list of accessories assigned to a specified user",
     *     operationId="getUserAccessories",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of user to get accessories for",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer", example=1),
     *             @OA\Property(
     *                 property="rows",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Logitech MX Keys"),
     *                     @OA\Property(property="category", type="object"),
     *                     @OA\Property(property="manufacturer", type="object"),
     *                     @OA\Property(property="company", type="object"),
     *                     @OA\Property(property="model_number", type="string", example="MX-123"),
     *                     @OA\Property(property="created_at", type="object"),
     *                     @OA\Property(property="updated_at", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function accessories($id)
    {
        $this->authorize('view', User::class);
        $user = User::findOrFail($id);
        $this->authorize('view', Accessory::class);
        $accessories = $user->accessories;

        return (new AccessoriesTransformer)->transformAccessories($accessories, $accessories->count());
    }

    /**
     * Return JSON containing a list of licenses assigned to a user.
     *
     * @author [N. Mathar] [<snipe@snipe.net>]
     * @since [v5.0]
     * @param $userId
     * @return string JSON
     * 
     * @OA\Get(
     *     path="/api/v1/users/{user}/licenses",
     *     tags={"Users"},
     *     summary="List licenses assigned to a user",
     *     description="Returns a list of licenses assigned to a specified user",
     *     operationId="getUserLicenses",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of user to get licenses for",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer", example=1),
     *             @OA\Property(
     *                 property="rows",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Adobe Creative Cloud"),
     *                     @OA\Property(property="product_key", type="string", example="XXXX-XXXX-XXXX-XXXX"),
     *                     @OA\Property(property="expiration_date", type="object"),
     *                     @OA\Property(property="license_email", type="string", example="user@ncc.asia"),
     *                     @OA\Property(property="license_name", type="string", example="NCC User"),
     *                     @OA\Property(property="created_at", type="object"),
     *                     @OA\Property(property="updated_at", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function licenses($id)
    {
        $this->authorize('view', User::class);
        $this->authorize('view', License::class);
        $user = User::where('id', $id)->withTrashed()->first();
        $licenses = $user->licenses()->get();

        return (new LicensesTransformer())->transformLicenses($licenses, $licenses->count());
    }

    /**
     * Reset the user's two-factor status
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v3.0]
     * @param $userId
     * @return string JSON
     * 
     * @OA\Post(
     *     path="/api/v1/users/two_factor_reset",
     *     tags={"Users"},
     *     summary="Reset a user's two-factor authentication",
     *     description="Resets a user's two-factor authentication enrollment status and secret",
     *     operationId="postTwoFactorReset",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id"},
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 description="ID of the user to reset 2FA for",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Two-factor authentication successfully reset",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Two-factor authentication has been reset for this user.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="There was an error resetting two-factor authentication."
     *             )
     *         )
     *     )
     * )
     */
    public function postTwoFactorReset(Request $request)
    {
        $this->authorize('update', User::class);

        if ($request->filled('id')) {
            try {
                $user = User::find($request->get('id'));
                $user->two_factor_secret = null;
                $user->two_factor_enrolled = 0;
                $user->save();

                return response()->json(['message' => trans('admin/settings/general.two_factor_reset_success')], 200);
            } catch (\Exception $e) {
                return response()->json(['message' => trans('admin/settings/general.two_factor_reset_error')], 500);
            }
        }
        return response()->json(['message' => 'No ID provided'], 500);
    }

    /**
     * Get info on the current user.
     *
     * @author [Juan Font] [<juanfontalonso@gmail.com>]
     * @since [v4.4.2]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/v1/users/me",
     *     tags={"Users"},
     *     summary="Get current authenticated user information",
     *     description="Returns detailed information about the currently authenticated user",
     *     operationId="getCurrentUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="first_name", type="string", example="Admin"),
     *             @OA\Property(property="last_name", type="string", example="User"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatars/admin.jpg"),
     *             @OA\Property(property="permissions", type="object"),
     *             @OA\Property(property="role", type="string", example="admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - User not authenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getCurrentUserInfo(Request $request)
    {
        $user = (new UsersTransformer)->transformUser($request->user());
        if (Auth::user()->isAdmin()) {
            $user['role'] = "admin";
        } elseif (Auth::user()->isSuperUser()) {
            $user['role'] = "user";
        }
        return $user;
    }

    /**
     * Restore a soft-deleted user.
     *
     * @author [E. Taylor] [<dev@evantaylor.name>]
     * @param int $userId
     * @since [v6.0.0]
     * @return JsonResponse
     * 
     * @OA\Post(
     *     path="/api/v1/users/{user_id}/restore",
     *     tags={"Users"},
     *     summary="Restore a deleted user",
     *     description="Restores a previously soft-deleted user",
     *     operationId="restoreUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="ID of user to restore",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User restored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="payload", type="null"),
     *             @OA\Property(property="messages", type="string", example="User was successfully restored."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This action is unauthorized.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="messages", type="string", example="User does not exist."),
     *             @OA\Property(property="payload", type="null")
     *         )
     *     )
     * )
     */
    public function restore($userId = null)
    {
        // Get asset information
        $user = User::withTrashed()->find($userId);
        $this->authorize('delete', $user);
        if (isset($user->id)) {
            // Restore the user
            User::withTrashed()->where('id', $userId)->restore();

            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/users/message.success.restored')));
        }

        $id = $userId;
        return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/users/message.user_not_found', compact('id'))), 200);
    }

    /**
     * Authenticate user with Google OAuth (Deprecated)
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @deprecated This method is deprecated and no longer used. Use loginGoogleV2 instead.
     */
    public function loginGoogle(){
        if(Helper::checkValidEmail(request()->profile_obj['email'])){
            $user = [
                "first_name" => request()->profile_obj['familyName'],
                "last_name" => request()->profile_obj['givenName'],
                "email" => request()->profile_obj['email'],
                "username" => request()->profile_obj['email'],
                "social_id" => request()->profile_obj['googleId'],
                "access_token_social" => request()->client_secret['access_token'],
                "platform" => "google",
                "password" => Helper::generateEncyrptedPassword(),
                "permissions" => '{"superuser":"1","admin":"0","import":"0","reports.view":"0","assets.view":"0","assets.create":"0","assets.edit":"0","assets.delete":"0","assets.checkin":"0","assets.checkout":"0","assets.audit":"0","assets.view.requestable":"0","accessories.view":"0","accessories.create":"0","accessories.edit":"0","accessories.delete":"0","accessories.checkout":"0","accessories.checkin":"0","consumables.view":"0","consumables.create":"0","consumables.edit":"0","consumables.delete":"0","consumables.checkout":"0","licenses.view":"0","licenses.create":"0","licenses.edit":"0","licenses.delete":"0","licenses.checkout":"0","licenses.keys":"0","licenses.files":"0","components.view":"0","components.create":"0","components.edit":"0","components.delete":"0","components.checkout":"0","components.checkin":"0","kits.view":"0","kits.create":"0","kits.edit":"0","kits.delete":"0","kits.checkout":"0","users.view":"0","users.create":"0","users.edit":"0","users.delete":"0","models.view":"0","models.create":"0","models.edit":"0","models.delete":"0","categories.view":"0","categories.create":"0","categories.edit":"0","categories.delete":"0","departments.view":"0","departments.create":"0","departments.edit":"0","departments.delete":"0","statuslabels.view":"0","statuslabels.create":"0","statuslabels.edit":"0","statuslabels.delete":"0","customfields.view":"0","customfields.create":"0","customfields.edit":"0","customfields.delete":"0","suppliers.view":"0","suppliers.create":"0","suppliers.edit":"0","suppliers.delete":"0","manufacturers.view":"0","manufacturers.create":"0","manufacturers.edit":"0","manufacturers.delete":"0","depreciations.view":"0","depreciations.create":"0","depreciations.edit":"0","depreciations.delete":"0","locations.view":"0","locations.create":"0","locations.edit":"0","locations.delete":"0","companies.view":"0","companies.create":"0","companies.edit":"0","companies.delete":"0","self.two_factor":"0","self.api":"0","self.edit_location":"0","self.checkout_assets":"0"}'
            ];
            $userCreate = User::query()->updateOrcreate([
                "email" => $user['email']
            ], $user);

            $token = $userCreate->createToken('google-login')->accessToken;

            return response()->json([
                "token_type" => "Bear",
                "access_token" => $token,
            ]);
        } else {
            return response()->json([
                "message" => "Unauthorized",
            ], 401);
        }
    }

    /**
     * Authenticate user with Google OAuth (Version 2)
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/google",
     *     tags={"Authentication"},
     *     summary="Authenticate with Google OAuth (Enhanced)",
     *     description="Enhanced Google authentication with improved user matching and validation. Finds existing users by username extracted from email.",
     *     operationId="loginWithGoogleV2",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Google profile information",
     *         @OA\JsonContent(ref="#/components/schemas/GoogleProfileRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(ref="#/components/schemas/AuthenticationResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid email domain",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function loginGoogleV2(){
        $username = explode('@', request()->profile_obj['email'])[0];//todo check email
        $found = User::where('username', $username)->first();
        if(!$found) {
            return response()->json([
                "message" => "User not found",
            ], 400);
        }
        if(Helper::checkValidEmail(request()->profile_obj['email'])){
            $user = [
                "email" => request()->profile_obj['email'],
                "username" => $username,
                "social_id" => request()->profile_obj['googleId'],
                "access_token_social" => request()->client_secret['access_token'],
                // "platform" => "google",
                // "permissions" => '{"superuser":"1","admin":"0","import":"0","reports.view":"0","assets.view":"0","assets.create":"0","assets.edit":"0","assets.delete":"0","assets.checkin":"0","assets.checkout":"0","assets.audit":"0","assets.view.requestable":"0","accessories.view":"0","accessories.create":"0","accessories.edit":"0","accessories.delete":"0","accessories.checkout":"0","accessories.checkin":"0","consumables.view":"0","consumables.create":"0","consumables.edit":"0","consumables.delete":"0","consumables.checkout":"0","licenses.view":"0","licenses.create":"0","licenses.edit":"0","licenses.delete":"0","licenses.checkout":"0","licenses.keys":"0","licenses.files":"0","components.view":"0","components.create":"0","components.edit":"0","components.delete":"0","components.checkout":"0","components.checkin":"0","kits.view":"0","kits.create":"0","kits.edit":"0","kits.delete":"0","kits.checkout":"0","users.view":"0","users.create":"0","users.edit":"0","users.delete":"0","models.view":"0","models.create":"0","models.edit":"0","models.delete":"0","categories.view":"0","categories.create":"0","categories.edit":"0","categories.delete":"0","departments.view":"0","departments.create":"0","departments.edit":"0","departments.delete":"0","statuslabels.view":"0","statuslabels.create":"0","statuslabels.edit":"0","statuslabels.delete":"0","customfields.view":"0","customfields.create":"0","customfields.edit":"0","customfields.delete":"0","suppliers.view":"0","suppliers.create":"0","suppliers.edit":"0","suppliers.delete":"0","manufacturers.view":"0","manufacturers.create":"0","manufacturers.edit":"0","manufacturers.delete":"0","depreciations.view":"0","depreciations.create":"0","depreciations.edit":"0","depreciations.delete":"0","locations.view":"0","locations.create":"0","locations.edit":"0","locations.delete":"0","companies.view":"0","companies.create":"0","companies.edit":"0","companies.delete":"0","self.two_factor":"0","self.api":"0","self.edit_location":"0","self.checkout_assets":"0"}'// todo 
            ];
            $userCreate = User::query()->updateOrcreate([
                "username" => $username
            ], $user);

            $permissions = json_decode($userCreate->permissions, TRUE);
            $scopes = [];
            foreach($permissions as $key => $value){
                if($value == "1"){
                    $scopes[] = $key;
                }
            }

            $token = $userCreate->createToken('google-login', $scopes)->accessToken;
            return response()->json([
                "token_type" => "Bear",
                "access_token" => $token,
            ]);
        }
        else {
            return response()->json([
                "message" => "Unauthorized",
            ], 401);
        }
    }

    /**
     * Authenticate user with username and password
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Authenticate with username and password",
     *     description="Authenticates a user using traditional username/password credentials.",
     *     operationId="basicLogin",
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Login credentials",
     *         @OA\JsonContent(ref="#/components/schemas/BasicLoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(ref="#/components/schemas/AuthenticationResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *            @OA\Property(property="message", type="string", example="Login information is incorrect.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required|min:6',
        ]);

        if (!Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không chính xác',
            ], 401);
        }

        $user = User::where('username', $request['username'])->firstOrFail();

        $permissions = json_decode($user->permissions, TRUE);
        $scopes = [];
        foreach($permissions as $key => $value){
            if($value == "1"){
                $scopes[] = $key;
            }
        }

        $token = $user->createToken('google-login', $scopes)->accessToken;
        return response()->json([
            "token_type" => "Bear",
            "access_token" => $token,
        ]);
    }

    /**
     * Get Mezon OAuth2 authentication URL
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/mezon-auth-url",
     *     tags={"Authentication"},
     *     summary="Get Mezon OAuth2 authentication URL",
     *     description="Returns the URL to begin OAuth2 authentication with Mezon platform. The URL includes client_id, redirect_uri, response_type, scope, and state parameters.",
     *     operationId="getMezonAuthUrl",
     *     @OA\Response(
     *         response=200,
     *         description="Successfully generated authentication URL",
     *         @OA\JsonContent(ref="#/components/schemas/MezonAuthUrlResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server configuration error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Missing Mezon configuration")
     *         )
     *     )
     * )
     */
    public function mezonAuthUrl(Request $request)
    {
        $clientId = env('MEZON_CLIENT_ID');
        $redirectUri = env('MEZON_REDIRECT_URI');
        $mezonDomain = env('MEZON_DOMAIN');
        $state = $this->generateBase64State();
        
        $url = "{$mezonDomain}/oauth2/auth?client_id={$clientId}&redirect_uri={$redirectUri}&response_type=code&scope=openid offline&state={$state}";

        return response()->json([
            'url' => $url,
        ]);

    }

    /**
     * Complete Mezon OAuth2 authentication flow
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/mezon-login",
     *     tags={"Authentication"},
     *     summary="Complete Mezon OAuth2 authentication",
     *     description="Process the authorization code returned from Mezon OAuth2 and authenticate the user. Supports both existing users with mezon_id and new user creation for valid email domains.",
     *     operationId="mezonLogin",
     *     security={},
     *     @OA\RequestBody(
     *         required=true,
     *         description="OAuth2 authentication data from Mezon callback",
     *         @OA\JsonContent(ref="#/components/schemas/MezonOAuthRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(ref="#/components/schemas/AuthenticationResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Not ncc.asia email or not found mezon id")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Unauthorized")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Authentication failed with Mezon")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function mezonLogin(Request $request)
    {
        
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);
    
        // Retrieve the 'code' and 'state' from the request payload
        $code = $request->input('code');
        $state = $request->input('state');
        $mezonDomain = env('MEZON_DOMAIN');
    
        $tokenUrl = "{$mezonDomain}/oauth2/token";
        $mezonAuth = [
            'code' => $code,
            'state' => $state,
        ];
        try {
            $accessTokenInfo = $this->getMezonAccessToken($tokenUrl, $mezonAuth);
            $accessTokenValue = $accessTokenInfo['access_token'];
            $scope = $accessTokenInfo['scope'];
            $tokenType = $accessTokenInfo['token_type'];

            $mezonUserInfo = $this->getMezonUserProfile($accessTokenValue);
            $mezonUserEmail = $mezonUserInfo['sub'];
            $mezonUserAud = $mezonUserInfo['user_id'];

            $user = User::where('mezon_id', $mezonUserAud)->first();
            if ($user) {// process mezon id flow
                $permissions = $user->permissions ? json_decode($user->permissions, true) : [];
                $scopes = [];
                foreach ($permissions as $key => $value) {
                    if ($value == "1") {
                        $scopes[] = $key;
                    }
                }
    
                $token = $user->createToken('mezon-login', $scopes)->accessToken;
                return response()->json([
                    "token_type" => $tokenType,
                    "access_token" => $token,
                ]);
                // end flow mezon id
            }
            // incase check api
            [$emailNamePart, $domain] = explode('@', $mezonUserEmail);
            if ($domain != 'ncc.asia') {
                return response()->json([
                    'message' => 'Not ncc.asia email or not found mezon id',
                ], 401);
            }
            if(Helper::checkValidEmail( $mezonUserEmail )) { 
                $firstName = $emailNamePart;
                $lastName = '';
                if (strpos($emailNamePart, '.') !== false) {
                    [$lastName, $firstName] = explode('.', $emailNamePart);
                }

                $userData = [
                    'email' => $mezonUserEmail,
                    'username' => $emailNamePart,
                    'social_id' => $mezonUserAud,
                    'access_token_social' => $accessTokenValue,
                    'first_name' => $firstName, 
                    'last_name' => $lastName,
                ];
                
                $user = User::where('username', $emailNamePart)->first();
                if (!$user) {
                    $userData['permissions'] = '{"superuser":"1","admin":"0","import":"0","reports.view":"0","assets.view":"0","assets.create":"0","assets.edit":"0","assets.delete":"0","assets.checkin":"0","assets.checkout":"0","assets.audit":"0","assets.view.requestable":"0","accessories.view":"0","accessories.create":"0","accessories.edit":"0","accessories.delete":"0","accessories.checkout":"0","accessories.checkin":"0","consumables.view":"0","consumables.create":"0","consumables.edit":"0","consumables.delete":"0","consumables.checkout":"0","licenses.view":"0","licenses.create":"0","licenses.edit":"0","licenses.delete":"0","licenses.checkout":"0","licenses.keys":"0","licenses.files":"0","components.view":"0","components.create":"0","components.edit":"0","components.delete":"0","components.checkout":"0","components.checkin":"0","kits.view":"0","kits.create":"0","kits.edit":"0","kits.delete":"0","kits.checkout":"0","users.view":"0","users.create":"0","users.edit":"0","users.delete":"0","models.view":"0","models.create":"0","models.edit":"0","models.delete":"0","categories.view":"0","categories.create":"0","categories.edit":"0","categories.delete":"0","departments.view":"0","departments.create":"0","departments.edit":"0","departments.delete":"0","statuslabels.view":"0","statuslabels.create":"0","statuslabels.edit":"0","statuslabels.delete":"0","customfields.view":"0","customfields.create":"0","customfields.edit":"0","customfields.delete":"0","suppliers.view":"0","suppliers.create":"0","suppliers.edit":"0","suppliers.delete":"0","manufacturers.view":"0","manufacturers.create":"0","manufacturers.edit":"0","manufacturers.delete":"0","depreciations.view":"0","depreciations.create":"0","depreciations.edit":"0","depreciations.delete":"0","locations.view":"0","locations.create":"0","locations.edit":"0","locations.delete":"0","companies.view":"0","companies.create":"0","companies.edit":"0","companies.delete":"0","self.two_factor":"0","self.api":"0","self.edit_location":"0","self.checkout_assets":"0"}';
                }
                $userCreate = User::query()->updateOrcreate([
                    "username" => $emailNamePart
                ], $userData);
    
                $permissions = $userCreate->permissions ? json_decode($userCreate->permissions, true) : [];
                $scopes = [];
                foreach ($permissions as $key => $value) {
                    if ($value == "1") {
                        $scopes[] = $key;
                    }
                }
    
                $token = $userCreate->createToken('mezon-login', $scopes)->accessToken;
                return response()->json([
                    "token_type" => $tokenType,
                    "access_token" => $token,
                ]);
            }
            else {
                return response()->json([
                    "message" => "Unauthorized",
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    private function getMezonAccessToken(string $tokenUrl, array $mezonAuth)
    {
        $clientId = env('MEZON_CLIENT_ID');
        $clientSecret = env('MEZON_CLIENT_SECRET');
        $redirectUri = env('MEZON_REDIRECT_URI');
        $client = new Client();

        $bodyData = [
            'code' => $mezonAuth['code'],
            'state' => $mezonAuth['state'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'scope' => $mezonAuth['scope'] ?? '',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
        $res = $client->post($tokenUrl,[
            'form_params' => $bodyData,
        ]);

        return json_decode($res->getBody()->getContents(),true);
    }

    private function getMezonUserProfile(string $accessToken)
    {
        $client = new Client();
        $mezonDomain = env('MEZON_DOMAIN');
        $userProfileUrl =  "{$mezonDomain}/userinfo";
        $res = $client->get($userProfileUrl,[
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($res->getBody()->getContents(), true);
    }

    private function generateBase64State(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Authenticate user with Mezon using hash verification
     *
     * @author [A. Gianotto] [<snipe@snipe.net>]
     * @since [v4.0]
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Post(
     *     path="/api/v1/auth/mezon-login-by-hash",
     *     tags={"Authentication"},
     *     summary="Authenticate with Mezon using hash verification",
     *     description="Authenticate a user through Mezon using hash-based verification. Validates the hash using HMAC-SHA256 with the configured app token and creates or updates user accounts as needed.",
     *     operationId="mezonLoginByHash",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mezon hash authentication data",
     *         @OA\JsonContent(ref="#/components/schemas/MezonHashLoginRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful authentication",
     *         @OA\JsonContent(ref="#/components/schemas/AuthenticationResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request - Various authentication failures",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="messages", type="string", example="Invalid email address"),
     *                     @OA\Property(property="payload", type="null")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="messages", type="string", example="Authentication failed - Invalid hash key"),
     *                     @OA\Property(property="payload", type="null")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="messages", type="string", example="The user is disabled"),
     *                     @OA\Property(property="payload", type="null")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hash verification failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server configuration error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="messages", type="string", example="Server configuration error: App token not set"),
     *             @OA\Property(property="payload", type="null")
     *         )
     *     )
     * )
     */
    public function mezonLoginByHash(Request $request)
    {
        $request->validate([
            'dataCheck' => 'required|string',
            'hashKey' => 'required|string',
            'userEmail' => 'required|string|email',
            'userName' => 'required|string',
        ]);

        $dataCheck = $request->input('dataCheck');
        $hashKey = $request->input('hashKey');
        $userEmail = $request->input('userEmail');
        $userName = $request->input('userName');

        if (!Helper::checkValidEmail($userEmail)) {
            return response()->json(
                Helper::formatStandardApiResponse('error', null, 'Invalid email address'),
                400
            );
        }

        $appToken = env('MEZON_APP_TOKEN');
        if (!$appToken) {
            return response()->json(
                Helper::formatStandardApiResponse('error', null, 'Server configuration error: App token not set'),
                500
            );
        }

        try
        {
            $secretKey = hash_hmac('sha256', "WebAppData", $appToken, true);
            $computedHash = bin2hex(hash_hmac('sha256', $dataCheck, $secretKey, true));

            if ($computedHash !== $hashKey) {
                return response()->json(
                    Helper::formatStandardApiResponse('error', null, 'Authentication failed - Invalid hash key'),
                    400
                );
            }

            $user = User::where('username', $userName)->first();
            if ($user && $user->activated !== true) {
                return response()->json(
                    Helper::formatStandardApiResponse('error', null, 'The user is disabled'),
                    400
                );
            }
            if (!$user) {
                $firstName = '';
                $lastName = '';
                if (strpos($userName, '.') !== false) {
                    [$lastName, $firstName] = explode('.', $userName);
                } else {
                    $firstName = $userName;
                }

                $user = User::updateOrCreate(
                    ['username' => $userName],
                    [
                        'email' => $userEmail,
                        'username' => $userName,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'password' => Helper::generateEncyrptedPassword(),
                        'activated' => 1,
                        'permissions' => '{"superuser":"1","admin":"0","import":"0","reports.view":"0","assets.view":"0","assets.create":"0","assets.edit":"0","assets.delete":"0","assets.checkin":"0","assets.checkout":"0","assets.audit":"0","assets.view.requestable":"0","accessories.view":"0","accessories.create":"0","accessories.edit":"0","accessories.delete":"0","accessories.checkout":"0","accessories.checkin":"0","consumables.view":"0","consumables.create":"0","consumables.edit":"0","consumables.delete":"0","consumables.checkout":"0","licenses.view":"0","licenses.create":"0","licenses.edit":"0","licenses.delete":"0","licenses.checkout":"0","licenses.keys":"0","licenses.files":"0","components.view":"0","components.create":"0","components.edit":"0","components.delete":"0","components.checkout":"0","components.checkin":"0","kits.view":"0","kits.create":"0","kits.edit":"0","kits.delete":"0","kits.checkout":"0","users.view":"0","users.create":"0","users.edit":"0","users.delete":"0","models.view":"0","models.create":"0","models.edit":"0","models.delete":"0","categories.view":"0","categories.create":"0","categories.edit":"0","categories.delete":"0","departments.view":"0","departments.create":"0","departments.edit":"0","departments.delete":"0","statuslabels.view":"0","statuslabels.create":"0","statuslabels.edit":"0","statuslabels.delete":"0","customfields.view":"0","customfields.create":"0","customfields.edit":"0","customfields.delete":"0","suppliers.view":"0","suppliers.create":"0","suppliers.edit":"0","suppliers.delete":"0","manufacturers.view":"0","manufacturers.create":"0","manufacturers.edit":"0","manufacturers.delete":"0","depreciations.view":"0","depreciations.create":"0","depreciations.edit":"0","depreciations.delete":"0","locations.view":"0","locations.create":"0","locations.edit":"0","locations.delete":"0","companies.view":"0","companies.create":"0","companies.edit":"0","companies.delete":"0","self.two_factor":"0","self.api":"0","self.edit_location":"0","self.checkout_assets":"0"}',
                    ]
                );

            }

            $permissions = $user->permissions ? json_decode($user->permissions, true) : [];
            $scopes = [];
            foreach ($permissions as $key => $value) {
                if ($value === "1" || $value === 1) {
                    $scopes[] = $key;
                }
            }

            $token = $user->createToken('mezon-hash-login', $scopes)->accessToken;

            return response()->json([
                "token_type" => "Bearer",
                "access_token" => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/list-user-type",
     *     tags={"Users"},
     *     summary="Get list of all user types",
     *     description="Returns a list of all distinct user types in the system",
     *     operationId="getListUserType",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DatatablesResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getListUserType()
    {
        $list = User::select('users.user_type as name')->distinct()->get();
        return (new DatatablesTransformer)->transformDatatables($list);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/list-user-position",
     *     tags={"Users"},
     *     summary="Get list of all job positions",
     *     description="Returns a list of all distinct job position codes in the system",
     *     operationId="getListJobPosition",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/DatatablesResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function getListJobPosition()
    {
        $list = User::select('users.job_position_code as name')->distinct()->get();
        return (new DatatablesTransformer)->transformDatatables($list);
    }
}
