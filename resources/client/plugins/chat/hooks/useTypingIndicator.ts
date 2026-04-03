import { useEffect, useState, useCallback } from 'react';
import { echo } from '@app/common/echo';
import { apiClient } from '@app/common/http/api-client';
import debounce from 'lodash-es/debounce';

interface TypingUser {
  userId: number;
  userName: string;
}

interface TypingStartedEvent {
  userId: number;
  userName: string;
}

interface TypingStoppedEvent {
  userId: number;
}

/**
 * Hook for typing indicators in a conversation.
 */
export function useTypingIndicator(conversationId: number) {
  const [typingUsers, setTypingUsers] = useState<TypingUser[]>([]);

  // Listen for typing events
  useEffect(() => {
    if (!conversationId) return;

    const channel = echo.private(`conversation.${conversationId}`);

    channel
      .listen('.TypingStarted', (event: TypingStartedEvent) => {
        setTypingUsers((prev) => {
          // Remove existing entry for this user and add new one
          const filtered = prev.filter((u) => u.userId !== event.userId);
          return [...filtered, { userId: event.userId, userName: event.userName }];
        });
      })
      .listen('.TypingStopped', (event: TypingStoppedEvent) => {
        setTypingUsers((prev) => prev.filter((u) => u.userId !== event.userId));
      });

    return () => {
      channel.stopListening('.TypingStarted').stopListening('.TypingStopped');
    };
  }, [conversationId]);

  // Debounced function to notify backend of typing
  const notifyTyping = useCallback(
    debounce(async (isTyping: boolean) => {
      try {
        await apiClient.post(`/chat/conversations/${conversationId}/typing`, {
          is_typing: isTyping,
        });
      } catch (error) {
        console.error('Failed to send typing indicator:', error);
      }
    }, 300),
    [conversationId]
  );

  // Start typing notification
  const startTyping = useCallback(() => {
    notifyTyping(true);
  }, [notifyTyping]);

  // Stop typing notification
  const stopTyping = useCallback(() => {
    notifyTyping(false);
    notifyTyping.cancel();
  }, [notifyTyping]);

  return {
    typingUsers,
    startTyping,
    stopTyping,
  };
}
