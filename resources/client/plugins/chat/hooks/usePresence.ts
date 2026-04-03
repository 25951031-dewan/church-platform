import { useEffect, useState } from 'react';
import { echo } from '@app/common/echo';

interface OnlineUser {
  id: number;
  name: string;
  avatar: string | null;
}

/**
 * Hook for tracking online/offline presence of users.
 */
export function usePresence() {
  const [onlineUsers, setOnlineUsers] = useState<OnlineUser[]>([]);

  useEffect(() => {
    const channel = echo.join('online');

    channel
      .here((users: OnlineUser[]) => {
        setOnlineUsers(users);
      })
      .joining((user: OnlineUser) => {
        setOnlineUsers((prev) => [...prev, user]);
      })
      .leaving((user: OnlineUser) => {
        setOnlineUsers((prev) => prev.filter((u) => u.id !== user.id));
      });

    return () => {
      echo.leave('online');
    };
  }, []);

  const isOnline = (userId: number) => onlineUsers.some((u) => u.id === userId);

  return {
    onlineUsers,
    isOnline,
  };
}
