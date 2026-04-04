import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { ElementType } from 'react';
import { Users, CalendarDays, Mic, BookOpen, HandHeart, Mail, FileText, Star, Activity, TrendingUp, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-react';

interface DashboardStats {
  counts: {
    users: number; posts: number; events: number; sermons: number;
    prayer_requests: number; groups: number;
  };
  additional_stats: { 
    pending_prayers: number; 
    upcoming_events: number; 
    new_users_today: number;
    new_users_week: number;
  };
}

interface SystemHealth {
  status: 'healthy' | 'degraded';
  checks: Record<string, { status: string; message: string }>;
  php_version: string;
  laravel_version: string;
}

interface ActivityItem {
  type: string;
  message: string;
  created_at: string;
  user?: { id: number; name: string };
  resource?: { id: number; title?: string; subject?: string };
}

function StatCard({ label, value, icon: Icon, color, trend }: {
  label: string; value: number | undefined; icon: ElementType; color: string; trend?: number;
}) {
  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-5 hover:border-white/10 transition-colors">
      <div className="flex items-start justify-between">
        <div className={`w-10 h-10 rounded-lg ${color} flex items-center justify-center`}>
          <Icon size={18} className="text-white" aria-hidden="true" />
        </div>
        {trend !== undefined && (
          <span className={`text-xs font-medium ${trend >= 0 ? 'text-green-400' : 'text-red-400'}`}>
            {trend >= 0 ? '+' : ''}{trend}%
          </span>
        )}
      </div>
      <p className="text-2xl font-bold text-white mt-3">{value ?? '—'}</p>
      <p className="text-sm text-gray-400 mt-0.5">{label}</p>
    </div>
  );
}

function HealthIndicator({ status, label }: { status: string; label: string }) {
  const icons = {
    ok: <CheckCircle size={14} className="text-green-400" />,
    error: <XCircle size={14} className="text-red-400" />,
    warning: <AlertCircle size={14} className="text-amber-400" />,
  };
  return (
    <div className="flex items-center gap-2 text-sm">
      {icons[status as keyof typeof icons] || icons.warning}
      <span className="text-gray-300">{label}</span>
    </div>
  );
}

function ActivityFeed({ activities }: { activities: ActivityItem[] }) {
  const icons: Record<string, ElementType> = {
    user_joined: Users,
    sermon_created: Mic,
    prayer_created: HandHeart,
  };

  return (
    <div className="space-y-3">
      {activities.map((activity, i) => {
        const Icon = icons[activity.type] || Activity;
        return (
          <div key={i} className="flex items-start gap-3 text-sm">
            <div className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center flex-shrink-0">
              <Icon size={14} className="text-gray-400" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-gray-300 truncate">{activity.message}</p>
              <p className="text-xs text-gray-500">
                {new Date(activity.created_at).toLocaleDateString()}
              </p>
            </div>
          </div>
        );
      })}
    </div>
  );
}

export function DashboardPage() {
  const { data: stats, isLoading: statsLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => apiClient.get<{ data: DashboardStats }>('dashboard/stats').then(r => r.data.data),
  });

  const { data: health } = useQuery({
    queryKey: ['admin-health'],
    queryFn: () => apiClient.get<{ data: SystemHealth }>('admin/dashboard/health').then(r => r.data.data),
    staleTime: 60000,
  });

  const { data: activityData } = useQuery({
    queryKey: ['admin-activity'],
    queryFn: () => apiClient.get<{ data: ActivityItem[] }>('admin/dashboard/activity').then(r => r.data.data),
    staleTime: 30000,
  });

  const c = stats?.counts;
  const a = stats?.additional_stats;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-white">Dashboard</h1>
        {health && (
          <div className={`px-3 py-1 rounded-full text-xs font-medium ${
            health.status === 'healthy' 
              ? 'bg-green-500/10 text-green-400 border border-green-500/20' 
              : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'
          }`}>
            System {health.status}
          </div>
        )}
      </div>

      {statsLoading ? (
        <div className="text-gray-400 text-sm">Loading stats…</div>
      ) : (
        <>
          {/* Quick Stats */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard label="Total Members" value={c?.users} icon={Users} color="bg-indigo-600" />
            <StatCard label="Sermons" value={c?.sermons} icon={Mic} color="bg-purple-600" />
            <StatCard label="Events" value={c?.events} icon={CalendarDays} color="bg-blue-600" />
            <StatCard label="Prayer Requests" value={c?.prayer_requests} icon={HandHeart} color="bg-rose-600" />
          </div>

          {/* Secondary Stats */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatCard label="Groups" value={c?.groups} icon={Users} color="bg-emerald-600" />
            <StatCard label="Blog Posts" value={c?.posts} icon={FileText} color="bg-amber-600" />
            <StatCard label="New This Week" value={a?.new_users_week} icon={TrendingUp} color="bg-cyan-600" />
            <StatCard label="Upcoming Events" value={a?.upcoming_events} icon={Clock} color="bg-orange-600" />
          </div>

          {/* Alerts */}
          {a && a.pending_prayers > 0 && (
            <div className="bg-amber-600/10 border border-amber-500/20 rounded-xl p-4 text-sm text-amber-400">
              🙏 You have <strong>{a.pending_prayers}</strong> pending prayer request{a.pending_prayers !== 1 ? 's' : ''} to review.
            </div>
          )}

          {/* Two Column Layout */}
          <div className="grid md:grid-cols-2 gap-6">
            {/* System Health */}
            {health && (
              <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
                <h2 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                  <Activity size={16} />
                  System Health
                </h2>
                <div className="space-y-2.5">
                  {Object.entries(health.checks).map(([key, check]) => (
                    <HealthIndicator key={key} status={check.status} label={`${key}: ${check.message}`} />
                  ))}
                </div>
                <div className="mt-4 pt-4 border-t border-white/5 text-xs text-gray-500">
                  PHP {health.php_version} • Laravel {health.laravel_version}
                </div>
              </div>
            )}

            {/* Recent Activity */}
            {activityData && activityData.length > 0 && (
              <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
                <h2 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
                  <Clock size={16} />
                  Recent Activity
                </h2>
                <ActivityFeed activities={activityData.slice(0, 8)} />
              </div>
            )}
          </div>
        </>
      )}
    </div>
  );
}
