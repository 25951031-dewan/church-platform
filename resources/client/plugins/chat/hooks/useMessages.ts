import { useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { echo } from '@app/common/echo';
import { useEffect } from 'react';
import type { Message, PaginatedResponse } from '../types';

interface MessageSentEvent {
  message: Message;
}

/**
 * Fetch messages for a conversation with real-time updates via Echo.
 */
export function useMessages(conversationId: number | null) {
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: ['chat', 'messages', conversationId],
    queryFn: async () => {
      if (!conversationId) return [];
      const response = await apiClient.get<PaginatedResponse<Message>>(
        `/chat/conversations/${conversationId}/messages`
      );
      // Reverse to show oldest first
      return response.data.data.reverse();
    },
    enabled: !!conversationId,
    staleTime: 10 * 1000, // 10 seconds
  });

  // Subscribe to real-time message updates
  useEffect(() => {
    if (!conversationId) return;

    const channel = echo.private(`conversation.${conversationId}`);

    channel.listen('.MessageSent', (event: MessageSentEvent) => {
      queryClient.setQueryData<Message[]>(
        ['chat', 'messages', conversationId],
        (old = []) => {
          // Avoid duplicates
          if (old.some((m) => m.id === event.message.id)) {
            return old;
          }
          return [...old, event.message];
        }
      );

      // Also update conversation list to show latest message
      queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
    });

    return () => {
      channel.stopListening('.MessageSent');
      echo.leave(`conversation.${conversationId}`);
    };
  }, [conversationId, queryClient]);

  return query;
}
