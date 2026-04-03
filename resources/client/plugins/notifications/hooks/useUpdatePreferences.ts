import {useMutation, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {NotificationPreferences} from '../types';

export function useUpdatePreferences() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (preferences: Partial<NotificationPreferences>[]) =>
      apiClient.put('notifications/preferences', {preferences}),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['notifications', 'preferences']});
    },
  });
}

export function useRegisterPush() {
  return useMutation({
    mutationFn: (payload: {player_id: string; device_type?: 'web' | 'ios' | 'android'; device_name?: string}) =>
      apiClient.post('notifications/push/register', payload),
  });
}

export function useUnregisterPush() {
  return useMutation({
    mutationFn: (player_id: string) => apiClient.post('notifications/push/unregister', {player_id}),
  });
}
