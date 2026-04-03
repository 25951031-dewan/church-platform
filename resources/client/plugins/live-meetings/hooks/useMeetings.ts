import {useInfiniteQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {Meeting} from '../types';

interface PaginatedMeetings {
  pagination: {
    data: Meeting[];
    current_page: number;
    last_page: number;
  };
}

export function useMeetings(params: Record<string, string | number | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['live-meetings', params],
    queryFn: ({pageParam = 1}) =>
      apiClient.get<PaginatedMeetings>('meetings', {params: {...params, page: pageParam}}).then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: last =>
      last.pagination.current_page < last.pagination.last_page
        ? last.pagination.current_page + 1
        : undefined,
  });
}
