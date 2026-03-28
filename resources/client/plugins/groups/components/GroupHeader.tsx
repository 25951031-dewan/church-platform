import {Group, useJoinGroup, useLeaveGroup} from '../queries';

interface GroupHeaderProps {
  group: Group;
}

export function GroupHeader({group}: GroupHeaderProps) {
  const joinGroup = useJoinGroup();
  const leaveGroup = useLeaveGroup();

  const membership = group.current_user_membership;
  const isMember = membership?.status === 'approved';
  const isPending = membership?.status === 'pending';
  const isAdmin = membership?.role === 'admin';

  return (
    <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
      <div className="h-48 bg-gradient-to-r from-primary-400 to-primary-600">
        {group.cover_image && (
          <img
            src={group.cover_image}
            alt={group.name}
            className="w-full h-full object-cover"
          />
        )}
      </div>
      <div className="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{group.name}</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            {group.type === 'private' ? 'Private' : 'Public'} group
            {' \u00b7 '}
            {group.member_count} {group.member_count === 1 ? 'member' : 'members'}
          </p>
        </div>
        <div className="flex gap-2">
          {isMember ? (
            <>
              {isAdmin && (
                <button className="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                  Settings
                </button>
              )}
              <button
                onClick={() => leaveGroup.mutate(group.id)}
                disabled={leaveGroup.isPending}
                className="px-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Leave
              </button>
            </>
          ) : isPending ? (
            <button
              disabled
              className="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-md text-gray-500 cursor-not-allowed"
            >
              Request Pending
            </button>
          ) : (
            <button
              onClick={() => joinGroup.mutate(group.id)}
              disabled={joinGroup.isPending}
              className="px-4 py-2 text-sm bg-primary-600 text-white rounded-md hover:bg-primary-700"
            >
              {group.type === 'private' ? 'Request to Join' : 'Join Group'}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
