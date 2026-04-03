import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function MeetingManagerPage() {
  const queryClient = useQueryClient();
  const {data, isLoading} = useQuery({
    queryKey: ['admin', 'meetings'],
    queryFn: () => apiClient.get('admin/meetings').then(r => r.data),
  });

  const remove = useMutation({
    mutationFn: (id: number) => apiClient.delete(`admin/meetings/${id}`),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['admin', 'meetings']}),
  });

  const meetings = data?.pagination?.data ?? [];

  return (
    <div>
      <h1 className="mb-4 text-2xl font-bold">Meeting Manager</h1>
      {isLoading ? <p>Loading...</p> : (
        <div className="space-y-2">
          {meetings.map((meeting: any) => (
            <div key={meeting.id} className="rounded border p-3 text-sm">
              <p className="font-semibold">{meeting.title}</p>
              <p className="text-gray-500">{new Date(meeting.starts_at).toLocaleString()}</p>
              <button className="mt-2 text-xs text-red-600" onClick={() => remove.mutate(meeting.id)}>Delete</button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
