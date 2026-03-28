import { useChurchMembers, useRemoveMember, useJoinChurch, useLeaveChurch } from '../queries';
import type { Church } from '../queries';

interface Props {
    church: Church;
    currentUserId?: number;
}

export function ChurchMembersTab({ church, currentUserId }: Props) {
    const { data, fetchNextPage, hasNextPage } = useChurchMembers(church.id);
    const removeMember = useRemoveMember();
    const joinChurch = useJoinChurch();
    const leaveChurch = useLeaveChurch();

    const members = data?.pages.flatMap(p => p.data) ?? [];
    const isMember = !!church.current_user_membership;

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <h3 className="text-sm font-semibold">
                    {church.approved_members_count ?? 0} Members
                </h3>
                {currentUserId && (
                    isMember ? (
                        <button
                            onClick={() => leaveChurch.mutate(church.id)}
                            disabled={leaveChurch.isPending}
                            className="text-xs px-3 py-1.5 rounded-full border border-white/20 hover:bg-white/10 transition-colors"
                        >
                            Leave Church
                        </button>
                    ) : (
                        <button
                            onClick={() => joinChurch.mutate(church.id)}
                            disabled={joinChurch.isPending}
                            className="text-xs px-3 py-1.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                        >
                            Join Church
                        </button>
                    )
                )}
            </div>

            <ul className="space-y-2">
                {members.map(m => (
                    <li key={m.id} className="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5">
                        <div className="h-8 w-8 rounded-full bg-white/10 overflow-hidden shrink-0">
                            {m.user?.avatar && (
                                <img src={m.user.avatar} alt={m.user.name} className="h-full w-full object-cover" />
                            )}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">{m.user?.name}</p>
                            <p className="text-xs text-white/40 capitalize">{m.role}</p>
                        </div>
                        {church.is_church_admin && currentUserId && m.user_id !== currentUserId && (
                            <button
                                onClick={() => removeMember.mutate({ churchId: church.id, userId: m.user_id })}
                                className="text-xs text-red-400 hover:text-red-300 transition-colors"
                            >
                                Remove
                            </button>
                        )}
                    </li>
                ))}
            </ul>

            {hasNextPage && (
                <button
                    onClick={() => fetchNextPage()}
                    className="w-full text-xs text-white/50 hover:text-white/80 py-2 transition-colors"
                >
                    Load more
                </button>
            )}
        </div>
    );
}
