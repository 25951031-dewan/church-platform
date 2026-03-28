import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

const REACTION_EMOJI: Record<string, string> = {
  like: '\u{1F44D}',
  pray: '\u{1F64F}',
  amen: '\u{2728}',
  love: '\u{2764}\u{FE0F}',
  celebrate: '\u{1F389}',
};

interface ReactionBarProps {
  reactableId: number;
  reactableType: string;
  reactionCounts: Record<string, number>;
  currentUserReaction: string | null;
  queryKey: string[];
}

export function ReactionBar({
  reactableId,
  reactableType,
  reactionCounts,
  currentUserReaction,
  queryKey,
}: ReactionBarProps) {
  const [showPicker, setShowPicker] = useState(false);
  const queryClient = useQueryClient();

  const toggleMutation = useMutation({
    mutationFn: (type: string) =>
      apiClient.post('/reactions/toggle', {
        reactable_id: reactableId,
        reactable_type: reactableType,
        type,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
    },
  });

  const totalReactions = Object.values(reactionCounts).reduce((a, b) => a + b, 0);

  return (
    <div className="flex items-center gap-2 relative">
      {totalReactions > 0 && (
        <div className="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
          {Object.entries(reactionCounts).map(([type, count]) => (
            <span key={type} className="flex items-center gap-0.5">
              <span>{REACTION_EMOJI[type]}</span>
              <span>{count}</span>
            </span>
          ))}
        </div>
      )}

      <button
        onClick={() => setShowPicker(!showPicker)}
        className={`text-sm px-2 py-1 rounded ${
          currentUserReaction
            ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400'
            : 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
        }`}
      >
        {currentUserReaction ? REACTION_EMOJI[currentUserReaction] : 'React'}
      </button>

      {showPicker && (
        <div className="absolute bottom-full left-0 mb-1 flex gap-1 bg-white dark:bg-gray-800 rounded-full shadow-lg px-2 py-1 border dark:border-gray-700">
          {Object.entries(REACTION_EMOJI).map(([type, emoji]) => (
            <button
              key={type}
              onClick={() => {
                toggleMutation.mutate(type);
                setShowPicker(false);
              }}
              className={`text-xl hover:scale-125 transition-transform p-1 ${
                currentUserReaction === type ? 'bg-primary-100 dark:bg-primary-900/30 rounded-full' : ''
              }`}
              title={type}
            >
              {emoji}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
