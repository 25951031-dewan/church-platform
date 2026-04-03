import {useGroupMembers, GroupMember, useChangeMemberRole, useRemoveMember} from '../queries';
import {useAuth} from '@app/common/auth/use-auth';

interface GroupMembersListProps {
  groupId: number | string;
  currentUserRole?: string;
}

const roleBadgeColors: Record<string, string> = {
  admin: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  moderator: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  member: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
};

export function GroupMembersList({groupId, currentUserRole}: GroupMembersListProps) {
  const {data, isLoading} = useGroupMembers(groupId);
  const changeRole = useChangeMemberRole(groupId);
  const removeMember = useRemoveMember(groupId);
  const {user} = useAuth();

  const canManage = currentUserRole === 'admin' || currentUserRole === 'moderator';

  const members: GroupMember[] = data?.pages.flatMap((p: any) => p.data) ?? [];

  if (isLoading) {
    return <div className="text-center py-8 text-gray-500">Loading members...</div>;
  }

  return (
    <div className="space-y-2">
      {members.map(member => (
        <div
          key={member.id}
          className="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
        >
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm font-medium">
              {member.user.avatar ? (
                <img src={member.user.avatar} alt="" className="w-10 h-10 rounded-full" />
              ) : (
                member.user.name[0]
              )}
            </div>
            <div>
              <p className="text-sm font-medium text-gray-900 dark:text-white">{member.user.name}</p>
              <span className={`text-xs px-2 py-0.5 rounded-full ${roleBadgeColors[member.role]}`}>
                {member.role}
              </span>
            </div>
          </div>
          {canManage && member.user.id !== user?.id && member.role !== 'admin' && (
            <div className="flex gap-1">
              {member.role === 'member' && (
                <button
                  onClick={() => changeRole.mutate({memberId: member.id, role: 'moderator'})}
                  className="text-xs px-2 py-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded"
                >
                  Promote
                </button>
              )}
              <button
                onClick={() => removeMember.mutate(member.id)}
                className="text-xs px-2 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
              >
                Remove
              </button>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
