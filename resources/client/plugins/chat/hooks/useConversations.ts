import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { Conversation } from '../types';

interface ConversationsResponse {
  data: Conversation[];
}

/**
 * Fetch all conversations for the authenticated user.
 */
export function useConversations() {
  return useQuery({
    queryKey: ['chat', 'conversations'],
    queryFn: async () => {
      const response = await apiClient.get<ConversationsResponse>('/chat/conversations');
      return response.data.data;
    },
    staleTime: 30 * 1000, // 30 seconds
  });
}
