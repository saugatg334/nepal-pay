<?php
declare(strict_types=1);

namespace NepalPay\Helpers\Production;

use NepalPay\Core\Logger;

/**
 * Role-Based Access Control
 * 
 * WHY: Checking role === 'admin' is not scalable. A proper RBAC system:
 *      - Defines permissions (e.g., 'users.view', 'users.freeze')
 *      - Maps roles to permission sets
 *      - Allows role inheritance
 *      - Makes audit logging meaningful
 * 
 * This is a production-grade minimal RBAC suitable for fintech.
 */
class RBAC
{
    private static array $permissions = [
        // Guest permissions
        'guest' => [
            'auth.login',
            'auth.register',
            'auth.forgot_password',
        ],
        
        // Regular user permissions
        'user' => [
            'auth.login',
            'auth.logout',
            'auth.change_password',
            'wallet.view',
            'wallet.send',
            'wallet.receive',
            'wallet.transactions',
            'wallet.bills',
            'profile.view',
            'profile.edit',
            'notifications.view',
        ],
        
        // Merchant permissions
        'merchant' => [
            'user',
            'merchant.dashboard',
            'merchant.qr',
            'merchant.transactions',
            'merchant.analytics',
        ],
        
        // Admin permissions (inherits all user + merchant + admin-specific)
        'admin' => [
            'user',
            'merchant',
            'admin.dashboard',
            'admin.users.view',
            'admin.users.freeze',
            'admin.users.unfreeze',
            'admin.transactions.view',
            'admin.merchants.view',
            'admin.merchants.approve',
            'admin.analytics.view',
            'admin.settings.view',
            'admin.settings.edit',
            'admin.audit_logs.view',
        ],
        
        // Super admin (everything)
        'super_admin' => [
            'admin',
            'system.backup',
            'system.restore',
            'system.config',
        ],
    ];

    /**
     * Check if a role has a specific permission
     */
    public static function can(string $role, string $permission): bool
    {
        $permissions = self::getRolePermissions($role);
        return in_array($permission, $permissions, true) || in_array('*', $permissions, true);
    }

    /**
     * Check if a role can access any of the given permissions
     */
    public static function canAny(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($role, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a role can access all of the given permissions
     */
    public static function canAll(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::can($role, $permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions for a role (with inheritance resolved)
     */
    public static function getRolePermissions(string $role): array
    {
        if (!isset(self::$permissions[$role])) {
            Logger::warning('Unknown role checked', ['role' => $role]);
            return [];
        }

        $permissions = [];
        $toProcess = [$role];
        $processed = [];

        while (!empty($toProcess)) {
            $current = array_shift($toProcess);
            
            if (isset($processed[$current])) {
                continue;
            }
            $processed[$current] = true;

            foreach (self::$permissions[$current] ?? [] as $perm) {
                // If permission is another role, inherit from it
                if (isset(self::$permissions[$perm])) {
                    $toProcess[] = $perm;
                } else {
                    $permissions[] = $perm;
                }
            }
        }

        return array_unique($permissions);
    }

    /**
     * Get all defined roles
     */
    public static function getRoles(): array
    {
        return array_keys(self::$permissions);
    }

    /**
     * Check if a role exists
     */
    public static function roleExists(string $role): bool
    {
        return isset(self::$permissions[$role]);
    }

    /**
     * Enforce a permission check (throws if denied)
     */
    public static function enforce(string $role, string $permission): void
    {
        if (!self::can($role, $permission)) {
            Logger::security('Permission denied', [
                'role' => $role,
                'permission' => $permission,
                'ip' => Input::ip()
            ]);
            
            http_response_code(403);
            throw new \Exception('Insufficient permissions: ' . $permission);
        }
    }
}

