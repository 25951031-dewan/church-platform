import {useMutation, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

interface PrayButtonProps {
  prayerId: number;
  prayerCount: number;
  hasPrayed: boolean;
}

export function PrayButton({prayerId, prayerCount, hasPrayed}: PrayButtonProps) {
  const queryClient = useQueryClient();

  const toggleMutation = useMutation({
    mutationFn: () =>
      apiClient.post('/reactions/toggle', {
        reactable_id: prayerId,
        reactable_type: 'prayer_request',
        type: 'pray',
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId]});
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });

  return (
    <button
      onClick={() => toggleMutation.mutate()}
      disabled={toggleMutation.isPending}
      className={`inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-colors ${
        hasPrayed
          ? 'bg-primary-50 text-primary-700 border border-primary-200 dark:bg-primary-900/20 dark:text-primary-400 dark:border-primary-800'
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
      }`}
    >
      <span className="text-lg">🙏</span>
      <span>{hasPrayed ? 'Prayed' : 'I Prayed'}</span>
      {prayerCount > 0 && (
        <span className="ml-1 text-xs bg-white/50 dark:bg-black/20 px-1.5 py-0.5 rounded-full">
          {prayerCount}
        </span>
      )}
    </button>
  );
}
