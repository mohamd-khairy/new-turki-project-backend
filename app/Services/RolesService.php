<?php

namespace App\Services;

use App\Models\User;
use Error;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesService
{
    /**
     *  Define basic operations to be used for each model permissions.
     */
    public const BASIC_ROLES = ['admin'];

    /**
     *  Define basic operations to be used for each model permissions.
     */
    public const BASIC_OPERATIONS = ['create', 'read', 'update', 'delete'];

    public const ADITIONAL_OPERATIONS = [];

    public const ROLES = [
        'general_manager' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ], 'customer_servise' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ], 'production_manager' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ], 'logistic_manager' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ], 'store_manager' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ], 'delegate' => [
            'like' => 'admin',
            'type' => null,
            'permissions' => [],
            'models' => []
        ],
    ];

    public static function allRolesName(): array
    {
        return [
            'admin' => 'مدير النظام',
            'customer_servise' => 'خدمة عملاء',
            'production_manager' => 'مسئول الانتاج',
            'logistic_manager' => 'مسئول لوجيستي',
            'general_manager' => 'مشرف المبيعات',
            'store_manager' => 'مسئول المبيعات',
            'delegate' => 'مندوب',
        ];
    }
    /**
     * @return void
     */
    public static function handle(): void
    {
        self::createRole(); // create admin role and assign all permissions to this role

        $roles = collect(self::ROLES);

        $roles->map(function ($details, $role) {
            $role = Role::updateOrCreate([
                'name' => $role,
                'display_name' => self::getRoleArabicName($role)
            ]);

            $user = self::assignRoleToUsers($role->id);

            $permissions = self::handleCustomOperation($details['permissions']);

            if (isset($details['like']) && $details['like']) {
                $likeRole = Role::where('name', $details['like'])->with('permissions')->first();
                $rolePermissions = $likeRole->permissions ?? collect();

                if (isset($details['type']) && $details['type'] === 'exception') {
                    $permissions = $rolePermissions->reject(function ($permission) use ($permissions) {
                        return in_array($permission->name, $permissions, true);
                    })->pluck('name')->toArray();
                } else if (isset($details['type']) && $details['type'] === 'added') {
                    $permissions = array_merge($rolePermissions->pluck('name')->toArray(), $permissions);
                } else {
                    $permissions = $rolePermissions->pluck('name')->toArray();
                }
            }

            if (isset($details['models']) && !isset($details['like'])) {

                $modelPermissions = self::handleModelPermissions($details['models']);

                if (isset($details['type']) && $details['type'] == 'exception') {
                    $permissions = collect($modelPermissions)->reject(function ($permission) use ($permissions) {
                        return in_array($permission, $permissions, true);
                    })->toArray();
                } else if (isset($details['type']) && $details['type'] == 'added') {
                    $permissions = array_merge($modelPermissions, $permissions);
                } else {
                    $permissions = $modelPermissions;
                }
            }


            $role->syncPermissions(array_unique($permissions));
        });
    }

    /**
     * Create any role with basic permissions on specific models.
     * @param null[] $role
     * @param Collection|null $models
     * @return void
     */
    public static function createRole(array $role = [null], Collection $models = null): void
    {
        collect(array_filter(array_merge(self::BASIC_ROLES, $role)))->each(function ($item) use ($models) {
            // Retrieve the Arabic display name based on the role name
            $arabicDisplayName = self::getRoleArabicName($item);

            $user = self::assignRoleToUsers($item);

            $roleModel = Role::updateOrCreate(['name' => $item, 'display_name' => $arabicDisplayName]);

            $models = is_null($models) ? self::getModels() : $models;

            self::assignModelPermissionsToRole($roleModel, $models);
        });
    }

    /**
     * @param ...$exceptions
     * @return Collection
     */
    public static function getModels(...$exceptions): Collection
    {
        return collect(scandir(app_path('Models')))->filter(function ($file_or_directory) {

            return Str::contains($file_or_directory, 'php');
        })->map(function ($file) {

            return str_replace('.php', '', $file);
        })->filter(function ($model) use ($exceptions) {

            return !in_array($model, $exceptions, true);
        })->filter(function ($class) {
            $className = 'App\\Models\\' . ucfirst($class);

            $object = new $className();

            if (is_object($object) && $object->inPermission && $object->inPermission !== false) {
                return $class;
            }

            return null;
        });
    }

    /**
     * Create basic permissions for passed model.
     *
     * @param string $model_name
     * @return Collection
     */
    public static function createModelPermissions(string $model_name): Collection
    {
        $permissions = collect();

        $operations = self::prepareOperations($model_name);

        foreach ($operations as $operation) {
            $permissions->push(self::findOrCreatePermission($model_name, $operation));
        }

        return $permissions;
    }

    /**
     * @param string $model
     * @param string $operation
     * @return Permission
     */
    private static function findOrCreatePermission(string $model, string $operation): Permission
    {
        $model_name = Str::snake($model, '-');

        return Permission::firstOrCreate(
            [
                'name' => "{$operation}-{$model_name}",
                'guard_name' => 'web',
            ],
            [
                'name' => "{$operation}-{$model_name}",
                'display_name' => ucfirst($operation) . ' ' . $model,
                'group' => $model,
                'guard_name' => 'web',
            ]
        );
    }

    /**
     * Creating models' basic permissions (CRUD) and assign them to the role.
     *
     * @param Role $role
     * @param Collection $models
     */
    private static function assignModelPermissionsToRole(Role $role, Collection $models): void
    {
        $models->each(function ($model) use ($role) {

            // At first, we have to create all model permissions.
            $permissions = self::createModelPermissions($model);

            // then we assign all of these permissions to super-web role.
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * @param string $model_name
     * @return array
     */
    private static function prepareOperations(string $model_name): array
    {
        $additional_operations = self::ADITIONAL_OPERATIONS;
        $operations = self::BASIC_OPERATIONS;
        $object = false;

        try {
            $className = 'App\\Models\\' . $model_name;
            $object = new $className();
        } catch (Error $e) {
        }

        if ($object && is_object($object) && $object->inPermission !== false) {

            if (isset($object->basicOperations) && is_array($object->basicOperations)) {
                $operations = $object->basicOperations;
            }

            if (isset($object->specialOperations) && is_array($object->specialOperations)) {
                $operations = array_merge($operations, $object->specialOperations);
            }

            $operations = array_unique($operations);
        }

        if (collect($additional_operations)->contains('name', $model_name)) {
            $model_additional_operations = collect($additional_operations)->where('name', $model_name)->first();

            $operations = isset($model_additional_operations['basic']) && $model_additional_operations['basic']
                ? array_unique(array_merge($operations, $model_additional_operations['operations']))
                : $model_additional_operations['operations'];
        }

        return $operations ?? [];
    }

    /**
     * @param array $models
     * @return array
     */
    public static function handleCustomOperation(array $models): array
    {
        collect($models)->each(function ($model, $key) use (&$permissions) {
            collect($model)->map(function ($i) use ($key, &$permissions) {
                if ($i === 'basic') {
                    foreach (self::BASIC_OPERATIONS as $x) {
                        $permissions[] = "$x-$key";
                    }
                } elseif ($i === '*') {
                    foreach (self::prepareOperations($key) as $x) {
                        $permissions[] = "$x-$key";
                    }
                } else {
                    $permissions[] = "$i-$key";
                }
            });
        });

        return array_filter(array_unique($permissions ?? []));
    }


    public static function handleModelPermissions(array $models): array
    {
        collect($models)->each(function ($model) use (&$permissions) {
            $permissions[] = self::createModelPermissions($model)->pluck('name')->toArray();
        });

        return array_filter(array_unique(array_merge(...$permissions ?? [])));
    }

    public static function getRoleArabicName($key): string
    {
        return self::allRolesName()[$key] ?? ucfirst($key);
    }

    public static function assignRoleToUsers($role)
    {
        $user = User::updateOrCreate([
            'email' => $role . '@' . $role . '.com',
        ], [
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'mobile' => Str::random(10),
        ]);

        $user->assignRole($role);
    }
}
