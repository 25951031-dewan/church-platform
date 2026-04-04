<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class InstallerController extends Controller
{
    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }

    // Step 1: Welcome page - show requirements check
    public function welcome()
    {
        if ($this->isInstalled()) {
            return redirect('/');
        }

        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'fileinfo' => extension_loaded('fileinfo'),
            'gd' => extension_loaded('gd'),
        ];

        $permissions = [
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(storage_path('framework/cache')),
            'sessions_writable' => is_writable(storage_path('framework/sessions')),
            'views_writable' => is_writable(storage_path('framework/views')),
            'env_writable' => is_writable(base_path('.env')) || !file_exists(base_path('.env')),
        ];

        $allPassed = !in_array(false, $requirements) && !in_array(false, $permissions);

        return view('installer.welcome', compact('requirements', 'permissions', 'allPassed') + ['currentStep' => 1]);
    }

    // Step 2: Database configuration form
    public function database()
    {
        if ($this->isInstalled()) return redirect('/');
        return view('installer.database', ['currentStep' => 2]);
    }

    // Step 2 POST: Test and save database config
    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',
        ]);

        // Test connection using PDO
        try {
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database}",
                $request->db_username,
                $request->db_password
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            return back()->withErrors(['database' => 'Could not connect: ' . $e->getMessage()])->withInput();
        }

        // Update .env file
        $this->updateEnv([
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password ?? '',
        ]);

        session(['db_configured' => true]);

        return redirect('/install/admin');
    }

    // Step 3: Admin account setup + run full installation
    public function admin()
    {
        if ($this->isInstalled()) return redirect('/');
        return view('installer.admin', ['currentStep' => 3]);
    }

    // Step 3 POST: Run full installation
    public function saveAdmin(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/')->with('info', 'Already installed.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        try {
            // Generate app key if not set
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Drop all existing tables and run fresh migrations
            $tables = DB::select('SHOW TABLES');
            $dbName = config('database.connections.mysql.database', env('DB_DATABASE'));
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $dbName} ?? reset((array) $table);
                DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Run migrations and seeders
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            // Create admin user and assign super-admin role
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
            ]);

            $superAdminRole = \Common\Auth\Models\Role::where('slug', 'super-admin')->first();
            if ($superAdminRole) {
                $user->roles()->attach($superAdminRole->id);
            }

            // Create storage link (ignore if already exists on shared hosting)
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
            }

            // Mark as installed — prevents re-running installer
            File::put(storage_path('installed'), 'Installed on: ' . now());

            return redirect('/login')->with('success', 'Installation complete! Sign in to get started.');

        } catch (\Exception $e) {
            return back()->withErrors(['install' => 'Installation failed: ' . $e->getMessage()])->withInput();
        }
    }

    // Helper method to update .env values
    private function updateEnv(array $data)
    {
        $envFile = base_path('.env');

        if (!File::exists($envFile)) {
            File::copy(base_path('.env.example'), $envFile);
        }

        $envContent = File::get($envFile);

        foreach ($data as $key => $value) {
            $quotedValue = str_contains($value, ' ') ? '"' . $value . '"' : $value;

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$quotedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$quotedValue}";
            }
        }

        File::put($envFile, $envContent);
    }
}
