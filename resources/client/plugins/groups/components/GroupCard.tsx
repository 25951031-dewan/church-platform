import {Group, useJoinGroup, useLeaveGroup} from '../queries';

interface GroupCardProps {
  group: Group;
}

export function GroupCard({group}: GroupCardProps) {
  const joinGroup = useJoinGroup();
  const leaveGroup = useLeaveGroup();

  const membership = group.current_user_membership;
  const isMember = membership?.status === 'approved';
  const isPending = membership?.status === 'pending';

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div className="h-32 bg-gradient-to-r from-primary-400 to-primary-600">
        {group.cover_image && (
          <img
            src={group.cover_image}
            alt={group.name}
            className="w-full h-full object-cover"
          />
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-gray-900 dark:text-white text-lg">
          {group.name}
        </h3>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {group.type === 'private' ? 'Private' : 'Public'} group
          {' \u00b7 '}
          {group.member_count} {group.member_count === 1 ? 'member' : 'members'}
        </p>
        {group.description && (
          <p className="text-sm text-gray-600 dark:text-gray-300 mt-2 line-clamp-2">
            {group.description}
          </p>
        )}
        <div className="mt-4">
          {isMember ? (
            <button
              onClick={() => leaveGroup.mutate(group.id)}
              disabled={leaveGroup.isPending}
              className="w-full px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              Leave Group
            </button>
          ) : isPending ? (
            <button
              disabled
              className="w-full px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-md text-gray-500 cursor-not-allowed"
            >
              Request Pending
            </button>
          ) : (
            <button
              onClick={() => joinGroup.mutate(group.id)}
              disabled={joinGroup.isPending}
              className="w-full px-4 py-2 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700"
            >
              {group.type === 'private' ? 'Request to Join' : 'Join Group'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
