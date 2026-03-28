import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface PrayerUpdate {
  id: number;
  prayer_request_id: number;
  user_id: number;
  content: string;
  status_change: 'still_praying' | 'partially_answered' | 'answered' | 'no_change';
  user: {id: number; name: string; avatar: string | null} | null;
  created_at: string;
}

export interface PrayerRequest {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  subject: string;
  request: string;
  description: string | null;
  status: 'pending' | 'approved' | 'praying' | 'answered';
  is_public: boolean;
  is_anonymous: boolean;
  is_urgent: boolean;
  category: string | null;
  pastoral_flag: boolean;
  flagged_by: number | null;
  prayer_count: number;
  user_id: number | null;
  user: {id: number; name: string; avatar: string | null} | null;
  updates: PrayerUpdate[];
  reactions_count: number;
  updates_count: number;
  current_user_prayed: boolean;
  created_at: string;
}

// --- Queries ---

export function usePrayerWall(params: Record<string, string | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['prayers', 'wall', params],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get('prayer-requests', {params: {wall: 1, ...params, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function usePrayerRequest(prayerId: number | string) {
  return useQuery({
    queryKey: ['prayers', prayerId],
    queryFn: () => apiClient.get(`prayer-requests/${prayerId}`).then(r => r.data.prayer),
  });
}

export function usePrayerUpdates(prayerId: number | string) {
  return useInfiniteQuery({
    queryKey: ['prayers', prayerId, 'updates'],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get(`prayer-requests/${prayerId}/updates`, {params: {page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

// --- Mutations ---

export function useSubmitPrayer() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<PrayerRequest>) =>
      apiClient.post('prayer-requests', data).then(r => r.data.prayer),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });
}

export function useUpdatePrayer(prayerId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<PrayerRequest>) =>
      apiClient.put(`prayer-requests/${prayerId}`, data).then(r => r.data.prayer),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId]});
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });
}

export function useDeletePrayer() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (prayerId: number) => apiClient.delete(`prayer-requests/${prayerId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });
}

export function useModeratePrayer(prayerId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (status: string) =>
      apiClient.patch(`prayer-requests/${prayerId}/moderate`, {status}).then(r => r.data.prayer),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId]});
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });
}

export function useToggleFlag(prayerId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiClient.patch(`prayer-requests/${prayerId}/flag`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId]});
      queryClient.invalidateQueries({queryKey: ['prayers']});
    },
  });
}

export function useAddPrayerUpdate(prayerId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: {content: string; status_change?: string}) =>
      apiClient.post(`prayer-requests/${prayerId}/updates`, data).then(r => r.data.update),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId]});
      queryClient.invalidateQueries({queryKey: ['prayers', prayerId, 'updates']});
    },
  });
}
