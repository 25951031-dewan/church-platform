import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface Event {
  id: number;
  title: string;
  description: string;
  content: string | null;
  image: string | null;
  location: string | null;
  location_url: string | null;
  meeting_url: string | null;
  start_date: string;
  end_date: string | null;
  is_featured: boolean;
  is_active: boolean;
  registration_required: boolean;
  max_attendees: number | null;
  registration_link: string | null;
  slug: string;
  creator: {id: number; name: string; avatar: string | null} | null;
  attending_rsvps_count: number;
  rsvp_counts: Record<string, number>;
  current_user_rsvp: 'attending' | 'interested' | 'not_going' | null;
}

export interface EventRsvp {
  id: number;
  event_id: number;
  user_id: number;
  status: 'attending' | 'interested' | 'not_going';
}

// --- Queries ---

export function useEvents(params: Record<string, string | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['events', params],
    queryFn: ({pageParam = 1}) =>
      apiClient.get('events', {params: {...params, page: pageParam}}).then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useEvent(eventId: number | string) {
  return useQuery({
    queryKey: ['events', eventId],
    queryFn: () => apiClient.get(`events/${eventId}`).then(r => r.data.event),
  });
}

export function useEventAttendees(eventId: number | string) {
  return useInfiniteQuery({
    queryKey: ['events', eventId, 'attendees'],
    queryFn: ({pageParam = 1}) =>
      apiClient.get(`events/${eventId}/attendees`, {params: {page: pageParam}}).then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

// --- Mutations ---

export function useCreateEvent() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Event>) =>
      apiClient.post('events', data).then(r => r.data.event),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['events']});
    },
  });
}

export function useUpdateEvent(eventId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Event>) =>
      apiClient.put(`events/${eventId}`, data).then(r => r.data.event),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['events', eventId]});
      queryClient.invalidateQueries({queryKey: ['events']});
    },
  });
}

export function useDeleteEvent() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (eventId: number) => apiClient.delete(`events/${eventId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['events']});
    },
  });
}

export function useRsvp(eventId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (status: 'attending' | 'interested' | 'not_going') =>
      apiClient.post(`events/${eventId}/rsvp`, {status}).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['events', eventId]});
    },
  });
}

export function useCancelRsvp(eventId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiClient.delete(`events/${eventId}/rsvp`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['events', eventId]});
    },
  });
}
