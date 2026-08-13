<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has sales role
     */
    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    /**
     * Check if user has accountant role  
     */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /**
     * Check if user can access company settings (admin only)
     */
    public function canManageCompany(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can access POS
     */
    public function canUsePOS(): bool
    {
        return $this->isAdmin() || $this->isSales();
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get the user's role relationship
     */
    public function roleObject()
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    /**
     * Get user's direct permissions
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
                    ->withTimestamps();
    }

    /**
     * Get all permissions for user (both direct and through role)
     */
    public function getAllPermissions()
    {
        $directPermissions = $this->permissions()->active()->get();
        $rolePermissions = collect();

        if ($this->roleObject) {
            $rolePermissions = $this->roleObject->permissions()->active()->get();
        }

        return $directPermissions->merge($rolePermissions)->unique('id');
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permission): bool
    {
        // Admin has all permissions
        if ($this->isAdmin()) {
            return true;
        }

        if (is_string($permission)) {
            // Check direct permissions
            if ($this->permissions()->where('slug', $permission)->where('is_active', true)->exists()) {
                return true;
            }

            // Check role permissions
            if ($this->roleObject && $this->roleObject->permissions()->where('slug', $permission)->where('is_active', true)->exists()) {
                return true;
            }

            return false;
        }

        // Check by permission object
        return $this->getAllPermissions()->contains('id', $permission->id);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Give permission to user
     */
    public function givePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
        }

        if ($permission && !$this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }

        return $this;
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    /**
     * Sync user permissions
     */
    public function syncPermissions(array $permissions)
    {
        $permissionIds = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('slug', $permission)->first();
                if ($perm) {
                    $permissionIds[] = $perm->id;
                }
            } else {
                $permissionIds[] = $permission;
            }
        }

        $this->permissions()->sync($permissionIds);
        return $this;
    }

    /**
     * Check specific business permissions
     */
    public function canViewSales(): bool
    {
        return $this->hasPermission('sales.view') || $this->isAdmin() || $this->isSales();
    }

    public function canCreateSales(): bool
    {
        return $this->hasPermission('sales.create') || $this->isAdmin() || $this->isSales();
    }

    public function canEditSales(): bool
    {
        return $this->hasPermission('sales.edit') || $this->isAdmin();
    }

    public function canDeleteSales(): bool
    {
        return $this->hasPermission('sales.delete') || $this->isAdmin();
    }

    public function canViewReports(): bool
    {
        return $this->hasPermission('reports.view') || $this->isAdmin() || $this->isAccountant();
    }

    public function canViewFinancialReports(): bool
    {
        return $this->hasPermission('reports.financial') || $this->isAdmin() || $this->isAccountant();
    }

    public function canManageProducts(): bool
    {
        return $this->hasPermission('products.manage') || $this->isAdmin();
    }

    public function canViewProducts(): bool
    {
        return $this->hasPermission('products.view') || $this->hasAnyPermission(['products.manage', 'sales.create']) || 
               $this->isAdmin() || $this->isSales();
    }

    public function canManageCustomers(): bool
    {
        return $this->hasPermission('customers.manage') || $this->isAdmin() || $this->isSales();
    }

    public function canManageSuppliers(): bool
    {
        return $this->hasPermission('suppliers.manage') || $this->isAdmin();
    }

    public function canManagePurchases(): bool
    {
        return $this->hasPermission('purchases.manage') || $this->isAdmin();
    }

    public function canViewInventory(): bool
    {
        return $this->hasPermission('inventory.view') || $this->isAdmin() || $this->isSales();
    }

    public function canManageInventory(): bool
    {
        return $this->hasPermission('inventory.manage') || $this->isAdmin();
    }

    public function canManageSettings(): bool
    {
        return $this->hasPermission('settings.manage') || $this->isAdmin();
    }

    public function canBackupSystem(): bool
    {
        return $this->hasPermission('system.backup') || $this->isAdmin();
    }

    public function canApproveTransactions(): bool
    {
        return $this->hasPermission('transactions.approve') || $this->isAdmin();
    }

    public function canViewOwnSalesOnly(): bool
    {
        return $this->isSales() && !$this->isAdmin() && !$this->hasPermission('sales.view_all');
    }
}
