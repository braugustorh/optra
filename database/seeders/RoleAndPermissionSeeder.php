<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Array de Modelos para Recursos
        $models = [
            'user',
            'sede',
            'department',
            'position',
            'portfolio',
            'psychometric-evaluation',
            'answer-type',
            'competence',
            'evaluations-types',
            'question',
        ];

        // Acciones estándar de Filament
        $actions = ['view-any', 'view', 'create', 'update', 'delete', 'restore', 'force-delete'];

        $allPermissions = [];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                $permissionName = "{$action} {$model}";
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                $allPermissions[] = $permissionName;
            }
        }

        // 2. Permisos especiales para Páginas (Dashboards/Paneles)
        $pages = [
            'view-page my-psychometric-evaluations',
            'view-page nom035',
            'view-page psychometric-dashboard',
            'view-page risk-factor-test',
            'view-page risk-factor-test-org-enviroment',
            'view-page test-guia-i',
            'view-page take-internal-evaluation',
        ];

        foreach ($pages as $pagePermission) {
            Permission::firstOrCreate(['name' => $pagePermission, 'guard_name' => 'web']);
            $allPermissions[] = $pagePermission;
        }

        // 3. Crear el Rol Super Administrador y asignarle TODOS los permisos
        // Usamos firstOrCreate para evitar duplicados si se corre múltiples veces
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Administrador', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($allPermissions);

        // 4. Asegurarnos que el usuario 1 (el tuyo) tenga el rol
        $user = \App\Models\User::find(1);
        if ($user) {
            $user->assignRole($superAdminRole);
        }
        
        // También puedes asignar a 'Administrador' si lo tenías
        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        // Aquí podrías darle syncPermissions si quisieras
    }
}
