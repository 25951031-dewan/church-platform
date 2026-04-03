import {useQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {Meeting} from '../types';

export function useMeeting(meetingId: number | string) {
  return useQuery({
    queryKey: ['live-meetings', meetingId],
    queryFn: () => apiClient.get<{meeting: Meeting}>(`meetings/${meetingId}`).then(r => r.data.meeting),
    enabled: !!meetingId,
  });
}
