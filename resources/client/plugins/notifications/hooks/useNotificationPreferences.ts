import {useQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';
import type {NotificationPreferences} from '../types';

export function useNotificationPreferences() {
  return useQuery({
    queryKey: ['notifications', 'preferences'],
    queryFn: () =>
      apiClient
        .get<{preferences: NotificationPreferences[]}>('notifications/preferences')
        .then(r => r.data.preferences),
  });
}
