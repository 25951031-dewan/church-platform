<?php
// tests/Feature/UpdaterTest.php

use Plugins\Installer\Services\UpdaterService;

test('checkConcurrency throws when updating.lock exists', function () {
    file_put_contents(storage_path('updating.lock'), now()->toIso8601String());

    expect(fn () => (new UpdaterService())->checkConcurrency())
        ->toThrow(\RuntimeException::class, 'already in progress');

    unlink(storage_path('updating.lock'));
});

test('writeLock and releaseLock manage updating.lock', function () {
    $service = new UpdaterService();
    $service->writeLock();
    expect(file_exists(storage_path('updating.lock')))->toBeTrue();
    $service->releaseLock();
    expect(file_exists(storage_path('updating.lock')))->toBeFalse();
});

test('checkForUpdate returns version comparison array', function () {
    // Mock HTTP so tests do not hit GitHub API
    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response([
            'tag_name' => 'v2.0.0',
            'html_url' => 'https://github.com/example/release',
            'body'     => 'Release notes here',
        ], 200),
    ]);
    \Illuminate\Support\Facades\Cache::forget('church_platform_latest_release');

    $info = (new UpdaterService())->checkForUpdate();
    expect($info['latest'])->toBe('2.0.0');
    expect($info)->toHaveKey('update_available');
    expect($info)->toHaveKey('current');
});
