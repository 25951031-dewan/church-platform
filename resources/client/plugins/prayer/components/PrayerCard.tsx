import {Link} from 'react-router';
import type {PrayerRequest} from '../queries';

const STATUS_BADGE: Record<string, {label: string; className: string}> = {
  praying: {label: 'Praying', className: 'bg-blue-100 text-blue-700'},
  answered: {label: 'Answered', className: 'bg-green-100 text-green-700'},
  approved: {label: 'Active', className: 'bg-gray-100 text-gray-600'},
  pending: {label: 'Pending', className: 'bg-yellow-100 text-yellow-700'},
};

interface PrayerCardProps {
  prayer: PrayerRequest;
}

export function PrayerCard({prayer}: PrayerCardProps) {
  const status = STATUS_BADGE[prayer.status] ?? STATUS_BADGE.approved;

  return (
    <Link
      to={`/prayers/${prayer.id}`}
      className="block bg-[#161920] rounded-xl p-4 border border-white/10 hover:shadow-lg hover:shadow-black/20 transition-shadow"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <h3 className="text-sm font-semibold text-white truncate">
              {prayer.subject}
            </h3>
            {prayer.is_urgent && (
              <span className="px-1.5 py-0.5 text-xs font-medium bg-red-500/20 text-red-400 rounded border border-red-500/30">
                Urgent
              </span>
            )}
          </div>

          <p className="text-sm text-gray-400 line-clamp-2 mb-2">
            {prayer.request}
          </p>

          <div className="flex items-center gap-3 text-xs text-gray-400">
            <span>{prayer.name ?? 'Anonymous'}</span>
            {prayer.category && (
              <span className="px-1.5 py-0.5 bg-white/10 rounded capitalize">
                {prayer.category}
              </span>
            )}
            <span className={`px-1.5 py-0.5 rounded ${status.className}`}>{status.label}</span>
          </div>
        </div>

        <div className="flex flex-col items-center text-center shrink-0">
          <span className="text-lg">🙏</span>
          <span className="text-xs font-medium text-gray-400">{prayer.reactions_count ?? 0}</span>
        </div>
      </div>
    </Link>
  );
}
