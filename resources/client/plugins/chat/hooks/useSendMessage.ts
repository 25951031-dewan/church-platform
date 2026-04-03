import { useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { Message } from '../types';

interface SendMessagePayload {
  conversation_id: number;
  body?: string;
  type?: 'text' | 'image' | 'file' | 'audio';
  file_entry_id?: number;
}

interface SendMessageResponse {
  data: Message;
}

/**
 * Send a message to a conversation.
 */
export function useSendMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (payload: SendMessagePayload) => {
      const response = await apiClient.post<SendMessageResponse>(
        `/chat/conversations/${payload.conversation_id}/messages`,
        {
          body: payload.body,
          type: payload.type || 'text',
          file_entry_id: payload.file_entry_id,
        }
      );
      return response.data.data;
    },
    onSuccess: (message) => {
      // Optimistically add message to local cache
      queryClient.setQueryData<Message[]>(
        ['chat', 'messages', message.conversation_id],
        (old = []) => {
          // Avoid duplicates (Echo might have already added it)
          if (old.some((m) => m.id === message.id)) {
            return old;
          }
          return [...old, message];
        }
      );

      // Update conversations list
      queryClient.invalidateQueries({ queryKey: ['chat', 'conversations'] });
    },
  });
}
