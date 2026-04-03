import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export type MeetingPlatform = 'zoom' | 'google_meet' | 'youtube' | 'other';
export type RecurrenceRule = 'weekly' | 'biweekly' | 'monthly';

export interface Meeting {
  id: number;
  title: string;
  description: string | null;
  meeting_url: string;
  platform: MeetingPlatform;
  church_id: number | null;
  host_id: number;
  host: {id: number; name: string; avatar: string | null} | null;
  starts_at: string;
  ends_at: string;
  timezone: string;
  is_recurring: boolean;
  recurrence_rule: RecurrenceRule | null;
  cover_image: string | null;
  is_active: boolean;
  is_live: boolean;
  created_at: string;
  updated_at: string;
}

// --- Meeting Queries ---

export function useMeetings(params: Record<string, string | number | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['meetings', params],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get('meetings', {params: {...params, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.pagination?.current_page < last.pagination?.last_page
        ? last.pagination.current_page + 1
        : undefined,
  });
}

export function useLiveMeetings() {
  return useQuery({
    queryKey: ['meetings', 'live'],
    queryFn: () => apiClient.get('meetings/live').then(r => r.data.meetings as Meeting[]),
    refetchInterval: 60_000, // refresh every minute to detect live status changes
  });
}

export function useMeeting(meetingId: number | string) {
  return useQuery({
    queryKey: ['meetings', meetingId],
    queryFn: () =>
      apiClient.get(`meetings/${meetingId}`).then(r => r.data.meeting as Meeting),
    enabled: !!meetingId,
  });
}

// --- Meeting Mutations ---

export function useCreateMeeting() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Meeting>) =>
      apiClient.post('meetings', data).then(r => r.data.meeting as Meeting),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['meetings']});
    },
  });
}

export function useUpdateMeeting(meetingId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Meeting>) =>
      apiClient.put(`meetings/${meetingId}`, data).then(r => r.data.meeting as Meeting),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['meetings', meetingId]});
      queryClient.invalidateQueries({queryKey: ['meetings']});
    },
  });
}

export function useDeleteMeeting() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (meetingId: number) => apiClient.delete(`meetings/${meetingId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['meetings']});
    },
  });
}
