import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface Speaker {
  id: number;
  name: string;
  slug: string;
  bio: string | null;
  image: string | null;
  sermons_count: number;
}

export interface SermonSeries {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  sermons_count: number;
}

export interface Sermon {
  id: number;
  title: string;
  description: string | null;
  speaker: string;
  speaker_id: number | null;
  speaker_profile: Speaker | null;
  series: string | null;
  series_id: number | null;
  sermon_series: SermonSeries | null;
  scripture_reference: string | null;
  category: string | null;
  sermon_date: string | null;
  duration_minutes: number | null;
  audio_url: string | null;
  video_url: string | null;
  image: string | null;
  is_featured: boolean;
  is_published: boolean;
  view_count: number;
  slug: string;
  author: {id: number; name: string; avatar: string | null} | null;
  reaction_counts: Record<string, number>;
  current_user_reaction: string | null;
}

// --- Queries ---

export function useSermons(params: Record<string, string | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['sermons', params],
    queryFn: ({pageParam = 1}) =>
      apiClient.get('sermons', {params: {...params, page: pageParam}}).then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useSermon(sermonId: number | string) {
  return useQuery({
    queryKey: ['sermons', sermonId],
    queryFn: () => apiClient.get(`sermons/${sermonId}`).then(r => r.data.sermon),
  });
}

export function useSermonSeriesList() {
  return useQuery({
    queryKey: ['sermon-series'],
    queryFn: () => apiClient.get('sermon-series').then(r => r.data.data),
  });
}

export function useSermonSeries(seriesId: number | string) {
  return useQuery({
    queryKey: ['sermon-series', seriesId],
    queryFn: () => apiClient.get(`sermon-series/${seriesId}`).then(r => r.data.series),
  });
}

export function useSpeakers() {
  return useQuery({
    queryKey: ['speakers'],
    queryFn: () => apiClient.get('speakers').then(r => r.data.data),
  });
}

// --- Mutations ---

export function useCreateSermon() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Sermon>) =>
      apiClient.post('sermons', data).then(r => r.data.sermon),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['sermons']});
    },
  });
}

export function useUpdateSermon(sermonId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Sermon>) =>
      apiClient.put(`sermons/${sermonId}`, data).then(r => r.data.sermon),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['sermons', sermonId]});
      queryClient.invalidateQueries({queryKey: ['sermons']});
    },
  });
}

export function useDeleteSermon() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (sermonId: number) => apiClient.delete(`sermons/${sermonId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['sermons']});
    },
  });
}
