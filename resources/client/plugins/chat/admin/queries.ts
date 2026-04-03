import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import type { Conversation, Message, PaginatedResponse } from '../types';

/**
 * Fetch all conversations for admin moderation.
 */
export function useAdminConversations(page = 1) {
  return useQuery({
    queryKey: ['admin', 'chat', 'conversations', page],
    queryFn: async () => {
      const response = await apiClient.get<PaginatedResponse<Conversation>>(
        `/chat/admin/conversations?page=${page}`
      );
      return response.data;
    },
  });
}

/**
 * Fetch all messages for a conversation (admin view, includes deleted).
 */
export function useAdminMessages(conversationId: number | null, page = 1) {
  return useQuery({
    queryKey: ['admin', 'chat', 'messages', conversationId, page],
    queryFn: async () => {
      if (!conversationId) return null;
      const response = await apiClient.get<PaginatedResponse<Message>>(
        `/chat/admin/conversations/${conversationId}/messages?page=${page}`
      );
      return response.data;
    },
    enabled: !!conversationId,
  });
}

/**
 * Force delete a message (permanent).
 */
export function useForceDeleteMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (messageId: number) => {
      await apiClient.delete(`/chat/admin/messages/${messageId}/force`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'chat', 'messages'] });
    },
  });
}

/**
 * Restore a soft-deleted message.
 */
export function useRestoreMessage() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (messageId: number) => {
      const response = await apiClient.post<{ data: Message }>(
        `/chat/admin/messages/${messageId}/restore`
      );
      return response.data.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin', 'chat', 'messages'] });
    },
  });
}
