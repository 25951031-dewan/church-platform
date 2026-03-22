<?php

namespace App\Services;

use App\Core\MenuBuilder;
use App\Core\SettingsManager;
use App\Core\ThemeManager;
use App\Models\Church;

class CacheWarmingService
{
    public function __construct(
        private readonly SettingsManager  $settings,
        private readonly ThemeManager     $theme,
        private readonly MenuBuilder      $menu,
        private readonly PlatformModeService $platform,
    ) {}

    /**
     * Warm all critical caches. Call after deploy or cache:clear.
     */
    public function warmAll(): array
    {
        $warmed = [];

        // Global settings + menus + theme
        $this->settings->all();
        $warmed[] = 'settings:global';

        $this->theme->getTheme();
        $warmed[] = 'theme:global';

        $this->menu->getMenu();
        $warmed[] = 'menu:global';

        // Per-church caches in multi-church mode
        if ($this->platform->isMultiChurch()) {
            Church::active()->select('id')->chunk(50, function ($churches) use (&$warmed) {
                foreach ($churches as $church) {
                    $this->theme->getTheme($church->id);
                    $this->menu->getMenu($church->id);
                    $warmed[] = "theme:{$church->id}";
                    $warmed[] = "menu:{$church->id}";
                }
            });
        }

        return $warmed;
    }

    /**
     * Flush everything and re-warm in one step.
     */
    public function flushAndWarm(): array
    {
        $this->settings->flush();
        $this->theme->flushAll();
        $this->menu->flushAll();

        return $this->warmAll();
    }
}
