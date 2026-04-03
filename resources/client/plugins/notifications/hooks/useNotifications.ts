import {useInfiniteQuery, useQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {AppNotification} from '../types';

interface PaginatedNotifications {
  data: AppNotification[];
  current_page: number;
  last_page: number;
}

export function useNotifications(perPage = 20) {
  return useInfiniteQuery({
    queryKey: ['notifications', perPage],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get<PaginatedNotifications>('notifications', {params: {page: pageParam, per_page: perPage}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: last =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useUnreadCount() {
  return useQuery({
    queryKey: ['notifications', 'unread-count'],
    queryFn: () => apiClient.get<{count: number}>('notifications/unread-count').then(r => r.data.count),
    refetchInterval: 30_000,
  });
}
