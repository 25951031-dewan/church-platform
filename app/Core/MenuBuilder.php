<?php

namespace App\Core;

use App\Core\SettingsManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuBuilder
{
    private const CACHE_TTL = 3600;

    public function __construct(private readonly SettingsManager $settings) {}

    /**
     * Return the navigation menu items for a given church context.
     * In single-church mode this is always the global menu;
     * in multi-church mode each church can override the menu.
     */
    public function getMenu(?int $churchId = null): array
    {
        $key = 'menu:' . ($churchId ?? 'global');

        return $this->tagged()->remember($key, self::CACHE_TTL, function () use ($churchId) {
            return $this->buildMenu($churchId);
        });
    }

    public function flush(?int $churchId = null): void
    {
        $key = 'menu:' . ($churchId ?? 'global');

        try {
            Cache::tags(['menu', 'settings'])->forget($key);
        } catch (\BadMethodCallException) {
            Cache::forget($key);
        }
    }

    public function flushAll(): void
    {
        try {
            Cache::tags(['menu'])->flush();
        } catch (\BadMethodCallException) {
            Cache::forget('menu:global');
        }
    }

    private function buildMenu(?int $churchId): array
    {
        $toggles = $this->settings->getFeatureToggles();

        $items = [
            ['label' => 'Home',        'path' => '/',          'always' => true],
            ['label' => 'About',       'path' => '/about',     'always' => true],
            ['label' => 'Events',      'path' => '/events',    'feature' => 'events'],
            ['label' => 'Sermons',     'path' => '/sermons',   'feature' => 'sermons'],
            ['label' => 'Blog',        'path' => '/blog',      'feature' => 'blog'],
            ['label' => 'Prayer',      'path' => '/prayer',    'feature' => 'prayer'],
            ['label' => 'Bible',       'path' => '/bible',     'feature' => 'bible_reader'],
            ['label' => 'Hymns',       'path' => '/hymns',     'feature' => 'hymns'],
            ['label' => 'Library',     'path' => '/library',   'feature' => 'library'],
            ['label' => 'FAQ',         'path' => '/faq',       'always' => true],
            ['label' => 'Contact',     'path' => '/contact',   'always' => true],
        ];

        return array_values(array_filter($items, function ($item) use ($toggles) {
            if (! empty($item['always'])) return true;
            $feature = $item['feature'] ?? null;
            return $feature && ($toggles[$feature] ?? true);
        }));
    }

    private function tagged()
    {
        try {
            return Cache::tags(['menu', 'settings']);
        } catch (\BadMethodCallException) {
            return Cache::store();
        }
    }
}
