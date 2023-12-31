<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-permission', ['only' => ['index', 'permissions']]);
            $this->middleware('permission:create-permission', ['only' => ['store']]);
            $this->middleware('permission:update-permission', ['only' => ['update']]);
            $this->middleware('permission:delete-permission', ['only' => ['destroy']]);
        }
    }

    public function permissions()
    {
        $permissions = Permission::get()->groupBy('group');

        return successResponse($permissions);
    }

    public function index()
    {
        $permissions = Permission::with('roles')->latest()->get();
        return successResponse($permissions);
    }

    public function show($id)
    {
        $permission = Permission::find($id);
        return successResponse($permission);
    }

    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'display_name' => 'required',
            'group' => 'required'
        ]);

        if ($validate->fails()) {
            return failResponse($validate->messages()->first());
        }

        $data = $request->only([
            'name',
            'display_name',
            'group'
        ]);
        $data['guard_name'] = 'web';
        $permission = Permission::with('roles')->create($data);

        return successResponse($permission, 'permission has been created successfully');
    }

    public function update($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'display_name' => 'required',
            'group' => 'required'
        ]);

        if ($validate->fails()) {
            return failResponse($validate->messages()->first());
        }

        $data = $request->only([
            'name',
            'display_name',
            'group'
        ]);
        $permission = Permission::find($id);
        $permission->update($data);

        return successResponse($permission, 'permission has been updated successfully');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return successResponse([], 'permission has been deleted successfully');
    }
}
