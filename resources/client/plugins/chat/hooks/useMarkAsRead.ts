import { useMutation } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

/**
 * Mark a conversation as read.
 */
export function useMarkAsRead() {
  return useMutation({
    mutationFn: async (conversationId: number) => {
      await apiClient.post(`/chat/conversations/${conversationId}/read`);
    },
  });
}
