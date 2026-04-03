import {useQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {Meeting} from '../types';

export function useLiveMeetings() {
  return useQuery({
    queryKey: ['live-meetings', 'live'],
    queryFn: () => apiClient.get<{meetings: Meeting[]}>('meetings/live').then(r => r.data.meetings),
    refetchInterval: 60_000,
  });
}
