<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

class ManagePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:manage 
                            {action : The action to perform (list-roles, list-permissions, assign-role, assign-permission, create-user)}
                            {--user= : User ID or email}
                            {--role= : Role slug}
                            {--permission= : Permission slug}
                            {--name= : User name}
                            {--email= : User email}
                            {--password= : User password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage roles and permissions from command line';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list-roles':
                $this->listRoles();
                break;
            case 'list-permissions':  
                $this->listPermissions();
                break;
            case 'assign-role':
                $this->assignRole();
                break;
            case 'assign-permission':
                $this->assignPermission();
                break;
            case 'create-user':
                $this->createUser();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: list-roles, list-permissions, assign-role, assign-permission, create-user");
        }
    }

    private function listRoles()
    {
        $roles = Role::with('permissions')->get();
        
        $this->info("System Roles:");
        $this->newLine();
        
        foreach ($roles as $role) {
            $status = $role->is_active ? '✅' : '❌';
            $this->line("$status {$role->name} ({$role->slug})");
            $this->line("   Description: {$role->description}");
            $this->line("   Permissions: {$role->permissions->count()}");
            $this->line("   Users: {$role->users->count()}");
            $this->newLine();
        }
    }

    private function listPermissions()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $grouped = $permissions->groupBy('module');
        
        $this->info("System Permissions:");
        $this->newLine();
        
        foreach ($grouped as $module => $modulePermissions) {
            $this->line("📁 " . strtoupper($module ?: 'GENERAL'));
            foreach ($modulePermissions as $permission) {
                $status = $permission->is_active ? '✅' : '❌';
                $this->line("   $status {$permission->name} ({$permission->slug})");
                if ($permission->description) {
                    $this->line("      {$permission->description}");
                }
            }
            $this->newLine();
        }
    }

    private function assignRole()
    {
        $userInput = $this->option('user');
        $roleSlug = $this->option('role');

        if (!$userInput || !$roleSlug) {
            $this->error("Both --user and --role options are required");
            return;
        }

        // Find user by ID or email
        $user = is_numeric($userInput) 
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (!$user) {
            $this->error("User not found: {$userInput}");
            return;
        }

        // Find role
        $role = Role::where('slug', $roleSlug)->first();
        if (!$role) {
            $this->error("Role not found: {$roleSlug}");
            return;
        }

        // Assign role
        $user->role = $role->slug;
        $user->save();

        $this->info("✅ Role '{$role->name}' assigned to user '{$user->name}' ({$user->email})");
    }

    private function assignPermission()
    {
        $userInput = $this->option('user');  
        $permissionSlug = $this->option('permission');

        if (!$userInput || !$permissionSlug) {
            $this->error("Both --user and --permission options are required");
            return;
        }

        // Find user
        $user = is_numeric($userInput) 
            ? User::find($userInput)
            : User::where('email', $userInput)->first();

        if (!$user) {
            $this->error("User not found: {$userInput}");
            return;
        }

        // Find permission
        $permission = Permission::where('slug', $permissionSlug)->first();
        if (!$permission) {
            $this->error("Permission not found: {$permissionSlug}");
            return;
        }

        // Assign permission
        $user->givePermission($permission);

        $this->info("✅ Permission '{$permission->name}' assigned to user '{$user->name}' ({$user->email})");
    }

    private function createUser()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $roleSlug = $this->option('role');

        if (!$name || !$email || !$password || !$roleSlug) {
            $this->error("All options required: --name, --email, --password, --role");
            return;
        }

        // Check if role exists
        $role = Role::where('slug', $roleSlug)->first();
        if (!$role) {
            $this->error("Role not found: {$roleSlug}");
            return;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists");
            return;
        }

        // Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'role' => $roleSlug,
            'email_verified_at' => now(),
        ]);

        $this->info("✅ User created successfully:");
        $this->line("   Name: {$user->name}");
        $this->line("   Email: {$user->email}");
        $this->line("   Role: {$role->name}");
        $this->line("   ID: {$user->id}");
    }
}