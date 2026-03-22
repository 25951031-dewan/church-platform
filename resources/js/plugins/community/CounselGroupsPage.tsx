import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface CounselGroup {
    id: number;
    name: string;
    description: string | null;
    requires_approval: boolean;
    is_anonymous_posting: boolean;
    max_members: number | null;
    approved_members_count: number;
}

function GroupCard({ group }: { group: CounselGroup }) {
    const queryClient = useQueryClient();

    const requestJoin = useMutation({
        mutationFn: () => axios.post(`/api/v1/counsel-groups/${group.id}/request-join`),
        onSuccess:  () => queryClient.invalidateQueries({ queryKey: ['counsel-groups'] }),
    });

    const isFull = group.max_members !== null && group.approved_members_count >= group.max_members;

    return (
        <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div className="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h3 className="font-semibold text-gray-900">{group.name}</h3>
                    {group.description && (
                        <p className="mt-1 text-sm text-gray-500">{group.description}</p>
                    )}
                </div>

                <div className="flex shrink-0 flex-col items-end gap-1">
                    {group.is_anonymous_posting && (
                        <span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                            Anonymous
                        </span>
                    )}
                    {group.requires_approval && (
                        <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                            Approval required
                        </span>
                    )}
                </div>
            </div>

            <div className="mb-4 flex items-center gap-4 text-xs text-gray-400">
                <span>
                    {group.approved_members_count}
                    {group.max_members ? ` / ${group.max_members}` : ''} members
                </span>
            </div>

            <button
                type="button"
                disabled={isFull || requestJoin.isPending}
                onClick={() => requestJoin.mutate()}
                className={[
                    'w-full rounded-lg py-2 text-sm font-medium transition',
                    isFull
                        ? 'cursor-not-allowed bg-gray-100 text-gray-400'
                        : 'bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60',
                ].join(' ')}
            >
                {isFull
                    ? 'Group full'
                    : group.requires_approval
                    ? 'Request to join'
                    : 'Join group'}
            </button>

            {requestJoin.isSuccess && (
                <p className="mt-2 text-center text-xs text-green-600">
                    {group.requires_approval ? 'Request sent — awaiting approval.' : 'Joined successfully.'}
                </p>
            )}
            {requestJoin.isError && (
                <p className="mt-2 text-center text-xs text-red-500">
                    {(requestJoin.error as any)?.response?.data?.message ?? 'Failed to join. Try again.'}
                </p>
            )}
        </div>
    );
}

export default function CounselGroupsPage() {
    const { data, isLoading, isError } = useQuery<{ data: CounselGroup[] }>({
        queryKey: ['counsel-groups'],
        queryFn:  () => axios.get('/api/v1/counsel-groups').then(r => r.data),
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-24 text-gray-400">
                Loading…
            </div>
        );
    }

    if (isError) {
        return (
            <div className="py-12 text-center text-sm text-red-500">
                Failed to load counsel groups. Please try again.
            </div>
        );
    }

    const groups = data?.data ?? [];

    return (
        <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <div className="mb-8">
                <h1 className="text-3xl font-bold text-gray-900">Counsel Groups</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Private support groups facilitated by trained counsellors.
                </p>
            </div>

            {groups.length === 0 ? (
                <div className="rounded-lg border border-dashed border-gray-300 py-12 text-center text-sm text-gray-400">
                    No counsel groups available at this time.
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2">
                    {groups.map(group => (
                        <GroupCard key={group.id} group={group} />
                    ))}
                </div>
            )}
        </div>
    );
}
