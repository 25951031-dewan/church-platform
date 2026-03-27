<?php

namespace Common\Core;

use Common\Settings\Services\SettingService;

class BootstrapDataService
{
    public function __construct(
        private SettingService $settings,
        private PluginManager $plugins,
    ) {}

    public function get(): array
    {
        $user = auth()->user();

        return [
            'user' => $user ? $user->getBootstrapData() : null,
            'settings' => $this->getPublicSettings(),
            'plugins' => $this->plugins->getEnabled(),
        ];
    }

    private function getPublicSettings(): array
    {
        $all = $this->settings->getAll();
        $publicPrefixes = [
            'general.', 'theme.', 'seo.', 'landing.',
            'player.', 'captcha.site_key', 'gdpr.',
        ];

        return collect($all)
            ->filter(function ($value, $key) use ($publicPrefixes) {
                foreach ($publicPrefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) return true;
                }
                return false;
            })
            ->toArray();
    }
}
