<?php

namespace Plugins\Analytics\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Plugins\Analytics\Models\AnalyticsDaily;
use Plugins\Analytics\Models\PageView;

class AggregateAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $date = '',
    ) {}

    public function handle(): void
    {
        $date = $this->date ?: Carbon::yesterday()->toDateString();

        $start = "{$date} 00:00:00";
        $end   = "{$date} 23:59:59";

        // Aggregate page views and unique visitors per church
        $this->aggregatePageViews($date, $start, $end);

        // Aggregate new user registrations per church
        $this->aggregateNewUsers($date, $start, $end);

        // Aggregate active users (had a page view)
        $this->aggregateActiveUsers($date, $start, $end);

        // Aggregate content counts
        $this->aggregateContentMetrics($date, $start, $end);
    }

    private function aggregatePageViews(string $date, string $start, string $end): void
    {
        $rows = DB::table('page_views')
            ->selectRaw('church_id, COUNT(*) as total_views, COUNT(DISTINCT ip_hash) as unique_visitors')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('church_id')
            ->get();

        foreach ($rows as $row) {
            AnalyticsDaily::increment('page_views',      $row->total_views,      $row->church_id, $date);
            AnalyticsDaily::increment('unique_visitors', $row->unique_visitors,  $row->church_id, $date);
        }

        // Platform-wide totals (church_id = null)
        $totals = DB::table('page_views')
            ->selectRaw('COUNT(*) as total_views, COUNT(DISTINCT ip_hash) as unique_visitors')
            ->whereBetween('created_at', [$start, $end])
            ->first();

        if ($totals) {
            AnalyticsDaily::increment('page_views',      $totals->total_views,     null, $date);
            AnalyticsDaily::increment('unique_visitors', $totals->unique_visitors, null, $date);
        }
    }

    private function aggregateNewUsers(string $date, string $start, string $end): void
    {
        $count = DB::table('users')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        AnalyticsDaily::increment('new_users', $count, null, $date);
    }

    private function aggregateActiveUsers(string $date, string $start, string $end): void
    {
        $count = DB::table('page_views')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        AnalyticsDaily::increment('active_users', $count, null, $date);
    }

    private function aggregateContentMetrics(string $date, string $start, string $end): void
    {
        $metrics = [
            'posts'   => 'social_posts',
            'prayers' => 'prayers',
            'events'  => 'events',
            'sermons' => 'sermons',
        ];

        foreach ($metrics as $metric => $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $count = DB::table($table)
                ->whereBetween('created_at', [$start, $end])
                ->count();

            if ($count > 0) {
                AnalyticsDaily::increment($metric, $count, null, $date);
            }
        }
    }
}
