import {useState} from 'react';
import type {PrayerUpdate} from '../queries';
import {useAddPrayerUpdate, usePrayerUpdates} from '../queries';

const STATUS_LABELS: Record<string, string> = {
  still_praying: 'Still Praying',
  partially_answered: 'Partially Answered',
  answered: 'Answered',
  no_change: '',
};

interface PrayerUpdateThreadProps {
  prayerId: number;
  isOwner: boolean;
}

export function PrayerUpdateThread({prayerId, isOwner}: PrayerUpdateThreadProps) {
  const {data, fetchNextPage, hasNextPage, isFetchingNextPage} = usePrayerUpdates(prayerId);
  const addUpdate = useAddPrayerUpdate(prayerId);
  const [content, setContent] = useState('');
  const [statusChange, setStatusChange] = useState('no_change');

  const allUpdates: PrayerUpdate[] = data?.pages.flatMap((p: any) => p.data) ?? [];

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!content.trim()) return;
    addUpdate.mutate(
      {content: content.trim(), status_change: statusChange},
      {
        onSuccess: () => {
          setContent('');
          setStatusChange('no_change');
        },
      }
    );
  };

  return (
    <div className="space-y-4">
      <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Prayer Updates</h3>

      {isOwner && (
        <form onSubmit={handleSubmit} className="space-y-2">
          <textarea
            value={content}
            onChange={e => setContent(e.target.value)}
            placeholder="Share an update on this prayer..."
            rows={3}
            className="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
          <div className="flex items-center gap-2">
            <select
              value={statusChange}
              onChange={e => setStatusChange(e.target.value)}
              className="text-sm border border-gray-200 dark:border-gray-600 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
            >
              <option value="no_change">No status change</option>
              <option value="still_praying">Still Praying</option>
              <option value="partially_answered">Partially Answered</option>
              <option value="answered">Answered!</option>
            </select>
            <button
              type="submit"
              disabled={addUpdate.isPending || !content.trim()}
              className="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 disabled:opacity-50"
            >
              {addUpdate.isPending ? 'Posting...' : 'Post Update'}
            </button>
          </div>
        </form>
      )}

      {allUpdates.length === 0 ? (
        <p className="text-sm text-gray-400">No updates yet.</p>
      ) : (
        <div className="space-y-3">
          {allUpdates.map((update: PrayerUpdate) => (
            <div
              key={update.id}
              className="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 border border-gray-100 dark:border-gray-700"
            >
              <div className="flex items-center gap-2 mb-1">
                <span className="text-sm font-medium text-gray-900 dark:text-white">
                  {update.user?.name ?? 'Unknown'}
                </span>
                {update.status_change && update.status_change !== 'no_change' && (
                  <span className="px-1.5 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded">
                    {STATUS_LABELS[update.status_change]}
                  </span>
                )}
                <span className="text-xs text-gray-400 ml-auto">
                  {new Date(update.created_at).toLocaleDateString()}
                </span>
              </div>
              <p className="text-sm text-gray-600 dark:text-gray-300">{update.content}</p>
            </div>
          ))}
        </div>
      )}

      {hasNextPage && (
        <button
          onClick={() => fetchNextPage()}
          disabled={isFetchingNextPage}
          className="text-sm text-primary-600 hover:underline"
        >
          {isFetchingNextPage ? 'Loading...' : 'Load more updates'}
        </button>
      )}
    </div>
  );
}
