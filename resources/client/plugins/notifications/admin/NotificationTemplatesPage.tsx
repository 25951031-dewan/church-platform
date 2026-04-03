import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function NotificationTemplatesPage() {
  const queryClient = useQueryClient();
  const {data, isLoading} = useQuery({
    queryKey: ['admin', 'notification-templates'],
    queryFn: () => apiClient.get('admin/notification-templates').then(r => r.data),
  });

  const toggle = useMutation({
    mutationFn: (template: any) =>
      apiClient.put(`admin/notification-templates/${template.id}`, {is_active: !template.is_active}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['admin', 'notification-templates']}),
  });

  const templates = data?.data ?? [];

  return (
    <div>
      <h1 className="mb-4 text-2xl font-bold">Notification Templates</h1>
      {isLoading ? <p>Loading...</p> : (
        <div className="space-y-2">
          {templates.map((template: any) => (
            <div key={template.id} className="rounded border p-3 text-sm">
              <p className="font-semibold">{template.name}</p>
              <p className="text-gray-500">{template.type}</p>
              <button className="mt-2 text-xs text-primary-600" onClick={() => toggle.mutate(template)}>
                {template.is_active ? 'Disable' : 'Enable'}
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
