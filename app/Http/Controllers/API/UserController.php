<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataEntry\PageRequest;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-user', ['only' => ['index', 'show']]);
            $this->middleware('permission:create-user', ['only' => ['store']]);
            $this->middleware('permission:update-user', ['only' => ['update']]);
            $this->middleware('permission:delete-user', ['only' => ['destroy']]);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @param PageRequest $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $data = User::with(['roles', 'permissions'])->orderBy('id', 'desc')
            ->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'admin');
                if (request('role_id')) {
                    $q->where('id', request('role_id'));
                }
                if (request('role_name')) {
                    $q->where('name', request('role_name'));
                }
            })
            ->where('id', '!=', auth()->id());

        $data = request('per_page') == -1 ? $data->get() : $data->paginate(request('per_page', 20));

        return successResponse($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validateData = $request->validate([
            'username' => 'required|string|max:100',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'mobile' => 'required|unique:users',
            'age' => 'required|numeric',
            'gender' => 'required|numeric',
            'country_code' => 'required',
            'roles.*' => 'required|exists:roles,id'
        ]);

        $data = $request->except('avatar');
        $data['password'] = bcrypt($request->password);

        if ($request->avatar) {
            $avatar = UploadService::store($request->avatar, 'profile');
            $data['avatar'] = $avatar;
            $data['avatar_thumb'] = $avatar;
        }

        if ($request->is_active) {
            $data['is_active'] = $request->is_active == 'true' ? 1 : 0;
        }

        $user = User::create($data);

        $roles = isset($request->roles) ? (is_array($request->roles) ? $request->roles : explode(',', $request->roles)) : ['delegate'];
        $user->assignRole($roles);

        return successResponse($user, 'User has been successfully created');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);

        return successResponse($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {

        $validateData = $request->validate([
            'username' => 'required|string|max:100',
            'email' => 'required|string|max:255|unique:users,email,' . $id,
            'mobile' => 'required|unique:users,mobile,' . $id,
            'roles.*' => 'required|exists:roles,id',
            'country_code' => 'required',
        ]);

        $data = $request->except('password', 'avatar');

        $user = User::findOrFail($id);

        if ($request->avatar) {
            if ($user->avatar) {
                UploadService::delete($user->avatar);
            }

            $avatar = UploadService::store($request->avatar, 'profile');
            $data['avatar'] = $avatar;
            $data['avatar_thumb'] = $avatar;
        }

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->is_active) {
            $data['is_active'] = $request->is_active == 'true' ? 1 : 0;
        }

        $user->update($data);

        if ($request->has('roles')) {
            $roles = is_array($request->roles) ? $request->roles : explode(',', $request->roles);
            $user->roles()->sync($roles);
        }

        return successResponse($user, 'User has been successfully updated');
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $ids = empty(request('ids')) ? [$id] : explode(',', request('ids'));

        $user = User::select('id', 'avatar')->whereIn('id', $ids)->get()->map(function ($item) {
            UploadService::delete($item->avatar);
            $item->delete();
        });

        return successResponse($user, 'User has been successfully deleted');
    }
}
