import {useParams} from 'react-router';
import {usePrayerRequest} from '../queries';
import {PrayButton} from '../components/PrayButton';
import {PrayerUpdateThread} from '../components/PrayerUpdateThread';
import {useBootstrapData} from '@app/common/core/bootstrap-data';

const STATUS_BADGE: Record<string, {label: string; className: string}> = {
  praying: {label: 'Praying', className: 'bg-blue-100 text-blue-700'},
  answered: {label: 'Answered', className: 'bg-green-100 text-green-700'},
  approved: {label: 'Active', className: 'bg-gray-100 text-gray-600'},
  pending: {label: 'Pending', className: 'bg-yellow-100 text-yellow-700'},
};

export function PrayerDetailPage() {
  const {prayerId} = useParams<{prayerId: string}>();
  const {data: prayer, isLoading} = usePrayerRequest(prayerId!);
  const {user} = useBootstrapData();

  if (isLoading) {
    return <div className="max-w-2xl mx-auto px-4 py-12 text-center text-gray-400">Loading...</div>;
  }

  if (!prayer) {
    return (
      <div className="max-w-2xl mx-auto px-4 py-12 text-center text-gray-400">
        Prayer request not found.
      </div>
    );
  }

  const status = STATUS_BADGE[prayer.status] ?? STATUS_BADGE.approved;
  const isOwner = user?.id === prayer.user_id;

  return (
    <div className="max-w-2xl mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-4">
        <div className="flex items-center gap-2 mb-2">
          <h1 className="text-xl font-bold text-gray-900 dark:text-white">{prayer.subject}</h1>
          {prayer.is_urgent && (
            <span className="px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded">
              Urgent
            </span>
          )}
        </div>
        <div className="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
          <span>{prayer.name ?? 'Anonymous'}</span>
          <span className={`px-1.5 py-0.5 rounded text-xs ${status.className}`}>
            {status.label}
          </span>
          {prayer.category && (
            <span className="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs capitalize">
              {prayer.category}
            </span>
          )}
          <span className="text-xs">{new Date(prayer.created_at).toLocaleDateString()}</span>
        </div>
      </div>

      {/* Request text */}
      <div className="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
        <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{prayer.request}</p>
        {prayer.description && (
          <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">{prayer.description}</p>
        )}
      </div>

      {/* I Prayed button */}
      <div className="mb-6">
        <PrayButton
          prayerId={prayer.id}
          prayerCount={prayer.prayer_count ?? prayer.reactions_count ?? 0}
          hasPrayed={prayer.current_user_prayed ?? false}
        />
      </div>

      {/* Pastoral flag indicator */}
      {prayer.pastoral_flag && (
        <div className="mb-6 px-3 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-400">
          Flagged for pastoral attention
        </div>
      )}

      {/* Prayer updates */}
      <PrayerUpdateThread prayerId={prayer.id} isOwner={isOwner} />
    </div>
  );
}
