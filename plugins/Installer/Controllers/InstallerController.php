<?php

namespace Plugins\Installer\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Plugins\Installer\Services\InstallerService;

class InstallerController extends Controller
{
    public function __construct(private InstallerService $service) {}

    public function step1(): View
    {
        $this->service->prepareEnvironment();
        $requirements = $this->service->checkRequirements();
        $allPassed = ! in_array(false, $requirements, true);

        return view('installer::installer.step1', compact('requirements', 'allPassed'));
    }

    public function postStep1(Request $request): RedirectResponse
    {
        $this->service->writeStep1Env($request->getSchemeAndHttpHost());

        // Redirect so next request re-bootstraps and loads APP_KEY from disk
        return redirect('/install/step2');
    }

    public function step2(): View
    {
        return view('installer::installer.step2');
    }

    public function postStep2(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'db_host' => ['required', 'string'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        if (! $this->service->testConnection([
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
        ])) {
            return back()->withErrors(['db_host' => 'Could not connect to database. Check credentials.'])->withInput();
        }

        $this->service->updateEnv([
            'APP_NAME' => $data['app_name'],
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ]);

        // Store app_name in session — config('app.name') is stale (bootstrapped before .env write).
        // Artisan::call('config:clear') removes the cache but does NOT reload in-memory config.
        session(['installer_app_name' => $data['app_name']]);

        Artisan::call('config:clear');
        $this->service->runMigrations();

        return redirect('/install/step3');
    }

    public function step3(): View
    {
        return view('installer::installer.step3');
    }

    public function postStep3(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $this->service->seedRoles();
        $admin = $this->service->createAdmin([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
        ]);
        // Read app_name from session — in-memory config() is stale from before step 2's .env write
        $this->service->createDefaultChurch(session('installer_app_name', 'Church Platform'), $admin->id);
        $this->service->createStorageLink();

        $this->service->updateEnv([
            'APP_INSTALLED' => 'true',
            'SESSION_DRIVER' => 'database',
            'SESSION_CONNECTION' => 'mysql',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        // CRITICAL: lock BEFORE warmCaches so route:cache excludes installer routes
        $this->service->lockInstaller();
        $this->service->warmCaches();

        // Render view before route cache takes effect (prevents 404 on `/install/complete`)
        return view('installer::installer.complete');
    }

    public function complete(): View
    {
        return view('installer::installer.complete');
    }
}
