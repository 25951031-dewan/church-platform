<?php

// tests/Feature/InstallerTest.php

use Illuminate\Support\Facades\File;
use Plugins\Installer\Services\InstallerService;
use Spatie\Permission\Models\Role;

test('updateEnv writes new key-value pairs atomically', function () {
    $envPath = sys_get_temp_dir().'/test_'.uniqid().'.env';
    file_put_contents($envPath, "APP_NAME=\"Old Name\"\nAPP_DEBUG=true\n");

    $service = new InstallerService($envPath);
    $service->updateEnv(['APP_NAME' => 'New Church', 'APP_KEY' => 'base64:abc123']);

    $contents = file_get_contents($envPath);
    expect($contents)->toContain('APP_NAME="New Church"');
    expect($contents)->toContain('APP_KEY=base64:abc123');
    expect($contents)->toContain('APP_DEBUG=true');

    unlink($envPath);
});

test('updateEnv handles multi-word values with quotes', function () {
    $envPath = sys_get_temp_dir().'/test_'.uniqid().'.env';
    file_put_contents($envPath, "APP_NAME=\"Church Platform\"\n");

    $service = new InstallerService($envPath);
    $service->updateEnv(['APP_NAME' => 'My Great Church']);

    expect(file_get_contents($envPath))->toContain('APP_NAME="My Great Church"');
    unlink($envPath);
});

test('prepareDirectories creates bootstrap/cache if missing', function () {
    $tempBase = sys_get_temp_dir().'/church_test_'.uniqid();
    mkdir($tempBase.'/storage/app', 0755, true);
    mkdir($tempBase.'/storage/framework/sessions', 0755, true);
    mkdir($tempBase.'/storage/logs', 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->prepareDirectories();

    expect(is_dir($tempBase.'/bootstrap/cache'))->toBeTrue();
    expect(is_writable($tempBase.'/bootstrap/cache'))->toBeTrue();

    File::deleteDirectory($tempBase);
});

test('writeRootHtaccess creates root .htaccess if missing', function () {
    $tempBase = sys_get_temp_dir().'/church_test_'.uniqid();
    mkdir($tempBase, 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->writeRootHtaccess();

    expect(file_exists($tempBase.'/.htaccess'))->toBeTrue();
    expect(file_get_contents($tempBase.'/.htaccess'))->toContain('RewriteRule');

    File::deleteDirectory($tempBase);
});

test('lockInstaller writes installed.lock inside the configured storage path', function () {
    $tempBase = sys_get_temp_dir().'/church_test_'.uniqid();
    mkdir($tempBase.'/storage', 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->lockInstaller();

    expect(file_exists($tempBase.'/storage/installed.lock'))->toBeTrue();
    expect(file_get_contents($tempBase.'/storage/installed.lock'))->toMatch('/^\d{4}-\d{2}-\d{2}/');

    File::deleteDirectory($tempBase);
});

test('seedRoles creates admin church_leader and member roles', function () {
    (new InstallerService)->seedRoles();

    expect(Role::where('name', 'admin')->exists())->toBeTrue();
    expect(Role::where('name', 'church_leader')->exists())->toBeTrue();
    expect(Role::where('name', 'member')->exists())->toBeTrue();
});
