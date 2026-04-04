import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { ElementType } from 'react';
import { Users, CalendarDays, Mic, BookOpen, HandHeart, Mail, FileText, Star } from 'lucide-react';

interface DashboardStats {
  counts: {
    users: number; posts: number; events: number; sermons: number;
    prayer_requests: number; books: number; subscribers: number;
  };
  additional_stats: { pending_reviews: number; unread_contacts: number; };
}

function StatCard({ label, value, icon: Icon, color }: {
  label: string; value: number | undefined; icon: ElementType; color: string;
}) {
  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
      <div className={`w-8 h-8 rounded-lg ${color} flex items-center justify-center mb-3`}>
        <Icon size={16} className="text-white" aria-hidden="true" />
      </div>
      <p className="text-2xl font-bold text-white">{value ?? '—'}</p>
      <p className="text-sm text-gray-400 mt-0.5">{label}</p>
    </div>
  );
}

export function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => apiClient.get<{ data: DashboardStats }>('dashboard/stats').then(r => r.data.data),
  });

  const c = data?.counts;
  const a = data?.additional_stats;

  return (
    <div>
      <h1 className="text-xl font-bold text-white mb-6">Dashboard</h1>

      {isLoading ? (
        <div className="text-gray-400 text-sm">Loading stats…</div>
      ) : (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <StatCard label="Members"         value={c?.users}           icon={Users}        color="bg-indigo-600" />
            <StatCard label="Sermons"          value={c?.sermons}         icon={Mic}          color="bg-purple-600" />
            <StatCard label="Events"           value={c?.events}          icon={CalendarDays} color="bg-blue-600" />
            <StatCard label="Prayer Requests"  value={c?.prayer_requests} icon={HandHeart}    color="bg-rose-600" />
            <StatCard label="Blog Posts"       value={c?.posts}           icon={FileText}     color="bg-amber-600" />
            <StatCard label="Library Books"    value={c?.books}           icon={BookOpen}     color="bg-teal-600" />
            <StatCard label="Subscribers"      value={c?.subscribers}     icon={Mail}         color="bg-cyan-600" />
            <StatCard label="Pending Reviews"  value={a?.pending_reviews} icon={Star}         color="bg-orange-600" />
          </div>

          {a && a.unread_contacts > 0 && (
            <div className="bg-amber-600/10 border border-amber-500/20 rounded-xl p-4 text-sm text-amber-400">
              📬 You have <strong>{a.unread_contacts}</strong> unread contact message{a.unread_contacts !== 1 ? 's' : ''}.
            </div>
          )}
        </>
      )}
    </div>
  );
}
