<?php

namespace App\Http\Controllers\Api;

use App\Core\MenuBuilder;
use App\Core\SettingsManager;
use App\Core\ThemeManager;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;

/**
 * Server-Driven UI Controller
 *
 * Returns JSON component trees that describe page layouts.
 * The client maps component `type` strings to React components,
 * enabling layout changes without frontend deploys.
 */
class SduiController extends Controller
{
    public function __construct(
        private readonly SettingsManager    $settings,
        private readonly ThemeManager       $theme,
        private readonly MenuBuilder        $menu,
        private readonly PlatformModeService $platform,
    ) {}

    /**
     * GET /api/v1/sdui/home
     * Returns the homepage SDUI layout with component types + data URIs.
     */
    public function home(): JsonResponse
    {
        $theme   = $this->theme->getTheme();
        $menu    = $this->menu->getMenu();
        $toggles = $this->settings->getFeatureToggles();

        $components = array_filter([
            $this->heroComponent(),
            $toggles['announcement'] ?? true  ? $this->component('AnnouncementBanner',  '/api/v1/announcements?limit=3') : null,
            $toggles['events']       ?? true  ? $this->component('EventsList',           '/api/v1/events?limit=4&upcoming=1') : null,
            $toggles['sermons']      ?? true  ? $this->component('SermonHighlights',     '/api/v1/sermons?limit=3') : null,
            $toggles['blog']         ?? true  ? $this->component('BlogPostGrid',         '/api/v1/posts?limit=6') : null,
            $toggles['verse']        ?? true  ? $this->component('DailyVerse',           '/api/v1/verse/today') : null,
            $toggles['prayer']       ?? true  ? $this->component('PrayerWallPreview',    '/api/v1/prayers?limit=5') : null,
            $this->ctaComponent(),
        ]);

        return response()->json([
            'version'    => 1,
            'type'       => 'screen',
            'key'        => 'home',
            'theme'      => $theme,
            'navigation' => $menu,
            'components' => array_values($components),
        ]);
    }

    /**
     * GET /api/v1/sdui/church/{id}
     * Returns the SDUI layout for a specific church page.
     */
    public function church(int $id): JsonResponse
    {
        $church = Church::active()->findOrFail($id);

        $theme   = $this->theme->getTheme($id);
        $menu    = $this->menu->getMenu($id);
        $toggles = $this->settings->getFeatureToggles();

        $components = array_filter([
            $this->churchHeroComponent($church),
            $toggles['events']   ?? true ? $this->component('EventsList',       "/api/v1/events?church_id={$id}&limit=4&upcoming=1") : null,
            $toggles['sermons']  ?? true ? $this->component('SermonHighlights', "/api/v1/sermons?church_id={$id}&limit=3") : null,
            $toggles['blog']     ?? true ? $this->component('BlogPostGrid',     "/api/v1/posts?church_id={$id}&limit=6") : null,
            $toggles['prayer']   ?? true ? $this->component('PrayerWallPreview',"/api/v1/prayers?church_id={$id}&limit=5") : null,
            $this->component('ChurchInfo', "/api/v1/churches/{$id}"),
        ]);

        return response()->json([
            'version'    => 1,
            'type'       => 'screen',
            'key'        => "church.{$id}",
            'theme'      => $theme,
            'navigation' => $menu,
            'components' => array_values($components),
        ]);
    }

    private function heroComponent(): array
    {
        return [
            'type'    => 'HeroBanner',
            'dataUri' => '/api/v1/settings/hero',
            'props'   => ['fullWidth' => true],
        ];
    }

    private function churchHeroComponent(Church $church): array
    {
        return [
            'type'  => 'ChurchHeroBanner',
            'props' => [
                'name'    => $church->name,
                'city'    => $church->city,
                'country' => $church->country,
                'logo'    => $church->logo ?? null,
                'cover'   => $church->cover_image ?? null,
            ],
        ];
    }

    private function ctaComponent(): array
    {
        return [
            'type'  => 'CallToAction',
            'props' => [
                'label'  => 'Join the Community',
                'action' => 'navigate',
                'target' => '/register',
            ],
        ];
    }

    private function component(string $type, string $dataUri): array
    {
        return ['type' => $type, 'dataUri' => $dataUri];
    }
}
