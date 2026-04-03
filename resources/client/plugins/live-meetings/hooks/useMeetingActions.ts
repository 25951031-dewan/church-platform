import {useMutation, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function useRegisterForMeeting(meetingId: number) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiClient.post(`meetings/${meetingId}/register`),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['live-meetings']}),
  });
}

export function useUnregisterForMeeting(meetingId: number) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () => apiClient.delete(`meetings/${meetingId}/register`),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['live-meetings']}),
  });
}
