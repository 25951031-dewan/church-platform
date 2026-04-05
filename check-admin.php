<?php
/**
 * Church Platform Database Status Checker
 * Run this to diagnose admin panel database issues
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🏛️  Church Platform Database Status\n";
echo "====================================\n\n";

try {
    // Test database connection
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connection: OK\n";
    
    // Check tables exist
    $tables = ['users', 'roles', 'permissions', 'user_role', 'permission_role', 'permission_user'];
    echo "\n📋 Table Status:\n";
    
    foreach ($tables as $table) {
        $exists = Schema::hasTable($table);
        $status = $exists ? '✅' : '❌';
        echo "   $status $table\n";
        
        if ($exists && in_array($table, ['users', 'roles', 'permissions'])) {
            $count = DB::table($table)->count();
            echo "      ($count records)\n";
        }
    }
    
    // Check if admin user exists with role
    echo "\n👤 Admin User Status:\n";
    
    $superAdminRole = DB::table('roles')->where('slug', 'super-admin')->first();
    if ($superAdminRole) {
        echo "   ✅ Super Admin role exists (ID: {$superAdminRole->id})\n";
        
        $adminUsers = DB::table('user_role')
            ->join('users', 'users.id', '=', 'user_role.user_id')
            ->where('user_role.role_id', $superAdminRole->id)
            ->select('users.id', 'users.email')
            ->get();
            
        if ($adminUsers->count() > 0) {
            echo "   ✅ Users with Super Admin role:\n";
            foreach ($adminUsers as $user) {
                echo "      - {$user->email} (ID: {$user->id})\n";
            }
        } else {
            echo "   ⚠️  No users have Super Admin role\n";
            echo "      Run: php artisan tinker --execute=\"App\\Models\\User::find(1)->roles()->attach({$superAdminRole->id})\"\n";
        }
    } else {
        echo "   ❌ Super Admin role not found\n";
        echo "      Run: php artisan db:seed --class=RoleSeeder\n";
    }
    
    // Check permissions
    echo "\n🔐 Permissions Status:\n";
    $permissionCount = DB::table('permissions')->count();
    $rolePermissionCount = DB::table('permission_role')->count();
    
    echo "   ✅ Permissions: $permissionCount\n";
    echo "   ✅ Role-Permission assignments: $rolePermissionCount\n";
    
    // Check plugins configuration
    echo "\n🔌 Plugins Status:\n";
    $pluginsConfig = config('plugins', []);
    $enabledPlugins = array_filter($pluginsConfig, fn($plugin) => $plugin['enabled'] ?? false);
    
    echo "   ✅ Total plugins configured: " . count($pluginsConfig) . "\n";
    echo "   ✅ Enabled plugins: " . count($enabledPlugins) . "\n";
    
    if (count($enabledPlugins) > 0) {
        echo "   📦 Enabled plugins:\n";
        foreach ($enabledPlugins as $name => $config) {
            $displayName = $config['name'] ?? ucfirst($name);
            echo "      - $displayName ($name)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🆘 Troubleshooting:\n";
    echo "1. Check database connection in .env\n";
    echo "2. Run migrations: php artisan migrate\n";
    echo "3. Run seeders: php artisan db:seed --class=PermissionSeeder && php artisan db:seed --class=RoleSeeder\n";
}

echo "\n🎯 Quick fixes:\n";
echo "1. Full setup: ./setup-admin.sh\n";
echo "2. Just assign admin: php artisan tinker --execute=\"App\\Models\\User::find(1)->roles()->attach(1)\"\n";
echo "3. Check logs: tail -f storage/logs/laravel.log\n";