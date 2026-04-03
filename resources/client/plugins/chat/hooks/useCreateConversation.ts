import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { Conversation } from '../types';

interface CreateConversationPayload {
  user_ids: number[];
  name?: string;
}

interface CreateConversationResponse {
  data: Conversation;
}

/**
 * Create a new conversation (direct or group).
 */
export function useCreateConversation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: CreateConversationPayload) => {
      const response = await apiClient.post<CreateConversationResponse>(
        '/chat/conversations',
        payload
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
    },
  });
}
