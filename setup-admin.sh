#!/bin/bash
# Church Platform Admin Setup Script

echo "🏛️  Church Platform Admin Setup"
echo "================================"

echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "🗄️  Running migrations..."
php artisan migrate --force

echo "🌱 Seeding permissions and roles..."
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=RoleSeeder --force

echo "👤 Checking admin user assignment..."
USER_ID=${1:-1}
php artisan tinker --execute="
\$user = \App\Models\User::find($USER_ID);
if (!\$user) {
    echo 'User ID $USER_ID not found. Please check your user ID.\n';
    exit(1);
}

\$superAdminRole = \Common\Auth\Models\Role::where('slug', 'super-admin')->first();
if (!\$superAdminRole) {
    echo 'Super Admin role not found. Please run RoleSeeder first.\n';
    exit(1);
}

if (!\$user->roles->contains(\$superAdminRole)) {
    \$user->roles()->attach(\$superAdminRole);
    echo 'Super Admin role assigned to user: ' . \$user->email . '\n';
} else {
    echo 'User already has Super Admin role: ' . \$user->email . '\n';
}
"

echo "🧹 Clearing caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Setup complete!"
echo ""
echo "🎯 Next steps:"
echo "1. Visit /admin to access the admin panel"
echo "2. Login with your user account"
echo "3. Check Users, Roles, and Plugins pages"
echo ""
echo "🆘 If you still see errors:"
echo "1. Check your database connection"
echo "2. Ensure your user has admin.access permission"
echo "3. Check Laravel logs: storage/logs/laravel.log"