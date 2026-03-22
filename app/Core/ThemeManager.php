<?php

namespace App\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ThemeManager
{
    private const CACHE_TTL = 3600;

    public function getTheme(?int $churchId = null): array
    {
        $key = 'theme:' . ($churchId ?? 'global');

        return $this->tagged()->remember($key, self::CACHE_TTL, function () use ($churchId) {
            $row = DB::table('settings')
                ->where('key', $churchId ? "church:{$churchId}:appearance" : 'appearance')
                ->first();

            return [
                'primary_color' => $row?->primary_color ?? '#2563eb',
                'logo'          => $row?->logo ?? null,
                'favicon'       => $row?->favicon ?? null,
                'custom_css'    => $row?->custom_css ?? '',
                'custom_js'     => $row?->custom_js ?? '',
            ];
        });
    }

    public function flush(?int $churchId = null): void
    {
        if ($churchId) {
            $this->tagged()->forget('theme:' . $churchId);
        } else {
            $this->flushAll();
        }
    }

    public function flushAll(): void
    {
        try {
            Cache::tags(['theme'])->flush();
        } catch (\BadMethodCallException) {
            // Tag-based invalidation not supported (file cache); clear known keys
            Cache::forget('theme:global');
        }
    }

    private function tagged(): \Illuminate\Cache\TaggedCache|\Illuminate\Contracts\Cache\Repository
    {
        try {
            return Cache::tags(['theme', 'settings']);
        } catch (\BadMethodCallException) {
            return Cache::store();
        }
    }
}
