<?php
// tests/Feature/InstallerTest.php

use Plugins\Installer\Services\InstallerService;

test('updateEnv writes new key-value pairs atomically', function () {
    $envPath = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
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
    $envPath = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
    file_put_contents($envPath, "APP_NAME=\"Church Platform\"\n");

    $service = new InstallerService($envPath);
    $service->updateEnv(['APP_NAME' => 'My Great Church']);

    expect(file_get_contents($envPath))->toContain('APP_NAME="My Great Church"');
    unlink($envPath);
});

test('prepareDirectories creates bootstrap/cache if missing', function () {
    $tempBase = sys_get_temp_dir() . '/church_test_' . uniqid();
    mkdir($tempBase . '/storage/app', 0755, true);
    mkdir($tempBase . '/storage/framework/sessions', 0755, true);
    mkdir($tempBase . '/storage/logs', 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->prepareDirectories();

    expect(is_dir($tempBase . '/bootstrap/cache'))->toBeTrue();
    expect(is_writable($tempBase . '/bootstrap/cache'))->toBeTrue();

    exec("rm -rf {$tempBase}");
});

test('writeRootHtaccess creates root .htaccess if missing', function () {
    $tempBase = sys_get_temp_dir() . '/church_test_' . uniqid();
    mkdir($tempBase, 0755, true);

    $service = new InstallerService(basePath: $tempBase);
    $service->writeRootHtaccess();

    expect(file_exists($tempBase . '/.htaccess'))->toBeTrue();
    expect(file_get_contents($tempBase . '/.htaccess'))->toContain('RewriteRule');

    exec("rm -rf {$tempBase}");
});
