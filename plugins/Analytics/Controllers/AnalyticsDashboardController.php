<?php

namespace Plugins\Analytics\Controllers;

use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Analytics\Models\AnalyticsDaily;
use Plugins\Analytics\Models\PageView;

class AnalyticsDashboardController extends Controller
{
    public function __construct(private readonly PlatformModeService $platform) {}

    /**
     * GET /api/v1/admin/analytics
     * Returns all data needed for the dashboard in a single request.
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->integer('church_id') ?: $this->platform->defaultChurch();
        $days     = $request->integer('days', 30);

        return response()->json([
            'stats'        => $this->stats($churchId),
            'daily_views'  => $this->dailyViews($churchId, $days),
            'top_pages'    => $this->topPages($churchId, $days),
            'user_roles'   => $this->userRoles(),
        ]);
    }

    // ─── Stats cards ──────────────────────────────────────────────────────────

    private function stats(?int $churchId): array
    {
        $totalUsers = DB::table('users')->count();

        $activeUsers7d = DB::table('page_views')
            ->where('created_at', '>=', now()->subDays(7))
            ->when($churchId, fn ($q) => $q->where('church_id', $churchId))
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $postsMonth = DB::getSchemaBuilder()->hasTable('social_posts')
            ? DB::table('social_posts')
                ->where('created_at', '>=', now()->startOfMonth())
                ->when($churchId, fn ($q) => $q->where('church_id', $churchId))
                ->count()
            : 0;

        $prayers = DB::getSchemaBuilder()->hasTable('prayers')
            ? DB::table('prayers')
                ->where('created_at', '>=', now()->startOfMonth())
                ->when($churchId, fn ($q) => $q->where('church_id', $churchId))
                ->count()
            : 0;

        return compact('totalUsers', 'activeUsers7d', 'postsMonth', 'prayers');
    }

    // ─── Line chart: daily views ──────────────────────────────────────────────

    private function dailyViews(?int $churchId, int $days): array
    {
        return AnalyticsDaily::metric('page_views')
            ->forChurch($churchId)
            ->last($days)
            ->orderBy('date')
            ->get(['date', 'value'])
            ->map(fn ($r) => ['date' => $r->date->toDateString(), 'views' => $r->value])
            ->values()
            ->toArray();
    }

    // ─── Bar chart: top pages ─────────────────────────────────────────────────

    private function topPages(?int $churchId, int $days): array
    {
        return DB::table('page_views')
            ->select('url', DB::raw('COUNT(*) as views'))
            ->where('created_at', '>=', now()->subDays($days))
            ->when($churchId, fn ($q) => $q->where('church_id', $churchId))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->toArray();
    }

    // ─── Pie chart: user roles ────────────────────────────────────────────────

    private function userRoles(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('model_has_roles')) {
            return [];
        }

        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as role', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
