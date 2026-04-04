<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Plugins\Blog\Models\Post;
use App\Plugins\Events\Models\Event;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Prayer\Models\PrayerRequest;
use App\Plugins\Sermons\Models\Sermon;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function stats(): JsonResponse
    {
        $counts = [
            'users' => User::count(),
            'sermons' => $this->safeCount(Sermon::class),
            'events' => $this->safeCount(Event::class),
            'prayer_requests' => $this->safeCount(PrayerRequest::class),
            'posts' => $this->safeCount(Post::class),
            'groups' => $this->safeCount(Group::class),
        ];

        $additionalStats = [
            'pending_prayers' => $this->safeCount(PrayerRequest::class, ['status' => 'pending']),
            'upcoming_events' => $this->safeCount(Event::class, ['upcoming' => true]),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_users_week' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];

        return response()->json([
            'data' => [
                'counts' => $counts,
                'additional_stats' => $additionalStats,
            ],
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);

        // User signups over time
        $userSignups = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Content creation over time
        $contentStats = [];
        
        if (class_exists(Sermon::class)) {
            $contentStats['sermons'] = Sermon::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');
        }

        if (class_exists(PrayerRequest::class)) {
            $contentStats['prayers'] = PrayerRequest::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');
        }

        return response()->json([
            'data' => [
                'period' => ['start' => $startDate->toDateString(), 'end' => now()->toDateString()],
                'user_signups' => $userSignups,
                'content' => $contentStats,
            ],
        ]);
    }

    public function recentActivity(): JsonResponse
    {
        $activities = collect();

        // Recent users
        $recentUsers = User::latest()
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'type' => 'user_joined',
                'message' => "{$u->name} joined",
                'created_at' => $u->created_at,
                'user' => ['id' => $u->id, 'name' => $u->name],
            ]);
        $activities = $activities->merge($recentUsers);

        // Recent sermons
        if (class_exists(Sermon::class)) {
            $recentSermons = Sermon::with('author:id,name')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($s) => [
                    'type' => 'sermon_created',
                    'message' => "Sermon \"{$s->title}\" published",
                    'created_at' => $s->created_at,
                    'resource' => ['id' => $s->id, 'title' => $s->title],
                ]);
            $activities = $activities->merge($recentSermons);
        }

        // Recent prayer requests
        if (class_exists(PrayerRequest::class)) {
            $recentPrayers = PrayerRequest::latest()
                ->take(5)
                ->get()
                ->map(fn($p) => [
                    'type' => 'prayer_created',
                    'message' => "New prayer request: \"{$p->subject}\"",
                    'created_at' => $p->created_at,
                    'resource' => ['id' => $p->id, 'subject' => $p->subject],
                ]);
            $activities = $activities->merge($recentPrayers);
        }

        // Sort by date and take latest 15
        $activities = $activities
            ->sortByDesc('created_at')
            ->take(15)
            ->values();

        return response()->json([
            'data' => $activities,
        ]);
    }

    public function systemHealth(): JsonResponse
    {
        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overallStatus = collect($health)->every(fn($h) => $h['status'] === 'ok')
            ? 'healthy'
            : 'degraded';

        return response()->json([
            'data' => [
                'status' => $overallStatus,
                'checks' => $health,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ]);
    }

    protected function safeCount(string $class, array $conditions = []): int
    {
        if (!class_exists($class)) {
            return 0;
        }

        $query = $class::query();
        
        foreach ($conditions as $key => $value) {
            if ($key === 'upcoming' && $value) {
                $query->where('start_date', '>=', now());
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Connection failed'];
        }
    }

    protected function checkCache(): array
    {
        try {
            cache()->put('health_check', true, 10);
            cache()->forget('health_check');
            return ['status' => 'ok', 'message' => 'Working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache error'];
        }
    }

    protected function checkStorage(): array
    {
        $storagePath = storage_path('app');
        if (is_writable($storagePath)) {
            return ['status' => 'ok', 'message' => 'Writable'];
        }
        return ['status' => 'error', 'message' => 'Not writable'];
    }

    protected function checkQueue(): array
    {
        $driver = config('queue.default');
        return ['status' => 'ok', 'message' => "Driver: {$driver}"];
    }
}
