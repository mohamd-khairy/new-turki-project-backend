<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-role', ['only' => ['index', 'show']]);
            $this->middleware('permission:create-role', ['only' => ['store']]);
            $this->middleware('permission:update-role', ['only' => ['update']]);
            $this->middleware('permission:delete-role', ['only' => ['destroy']]);
        }
    }

    public function index()
    {
        $roles = Role::with('permissions')
            ->when(!auth()->user()->hasRole('admin'), function ($query) {
                $query->whereNotIn('id', auth()->user()->roles->pluck('id')->toArray());
            })->get();
        return successResponse(RoleResource::collection($roles));
    }

    public function show($id)
    {
        $role = Role::find($id);
        $role['permissions'] = $role ? $role->permissions()->get()->pluck('name') : [];
        $permissions = Permission::get()->groupBy('group');

        return successResponse([
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'name' => "required|unique:roles,name,$id",
            'display_name' => 'required',
            'permissions' => 'required|array',
            'permissions.*' => 'required|exists:permissions,name',
        ]);

        if ($validate->fails()) {
            return failResponse($validate->messages()->first());
        }

        $data = $request->only(['name', 'display_name']);
        $role = Role::find($id);
        $role->update($data);

        $role->syncPermissions($request->permissions);

        return successResponse($role, 'Role has been updated successfully');
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'display_name' => 'required',
            'permissions' => 'required|array',
            'permissions.*' => 'required|exists:permissions,name',
        ]);

        if ($validate->fails()) {
            return failResponse($validate->messages()->first());
        }

        $data = $request->only([
            'name',
            'display_name',
        ]);
        $data['guard_name'] = 'web';
        $role = Role::create($data);
        $role->syncPermissions($request->permissions);

        return successResponse($role, 'Role has been created successfully');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return successResponse([], 'Role has been deleted successfully');
    }
}
