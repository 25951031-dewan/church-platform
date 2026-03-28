import {useState} from 'react';
import {useParams} from 'react-router';
import {useGroup} from '../queries';
import {GroupHeader} from '../components/GroupHeader';
import {GroupFeed} from '../components/GroupFeed';
import {GroupMembersList} from '../components/GroupMembersList';

type Tab = 'feed' | 'members' | 'about';

export function GroupDetailPage() {
  const {groupId} = useParams<{groupId: string}>();
  const {data: group, isLoading} = useGroup(groupId!);
  const [tab, setTab] = useState<Tab>('feed');

  if (isLoading || !group) {
    return <div className="text-center py-12 text-gray-500">Loading...</div>;
  }

  const membership = group.current_user_membership;
  const isMember = membership?.status === 'approved';
  const currentUserRole = membership?.role;

  const tabs: {key: Tab; label: string}[] = [
    {key: 'feed', label: 'Feed'},
    {key: 'members', label: `Members (${group.member_count})`},
    {key: 'about', label: 'About'},
  ];

  return (
    <div>
      <GroupHeader group={group} />
      <div className="max-w-3xl mx-auto px-4 py-4">
        <div className="flex gap-4 border-b border-gray-200 dark:border-gray-700 mb-4">
          {tabs.map(t => (
            <button
              key={t.key}
              onClick={() => setTab(t.key)}
              className={`pb-2 text-sm font-medium border-b-2 ${
                tab === t.key
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'
              }`}
            >
              {t.label}
            </button>
          ))}
        </div>
        {tab === 'feed' && (
          <GroupFeed groupId={group.id} isMember={isMember} />
        )}
        {tab === 'members' && (
          <GroupMembersList groupId={group.id} currentUserRole={currentUserRole} />
        )}
        {tab === 'about' && (
          <div className="space-y-4">
            {group.description && (
              <div>
                <h3 className="font-medium text-gray-900 dark:text-white mb-1">Description</h3>
                <p className="text-gray-600 dark:text-gray-300">{group.description}</p>
              </div>
            )}
            {group.rules && (
              <div>
                <h3 className="font-medium text-gray-900 dark:text-white mb-1">Group Rules</h3>
                <p className="text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{group.rules}</p>
              </div>
            )}
            <div>
              <h3 className="font-medium text-gray-900 dark:text-white mb-1">Privacy</h3>
              <p className="text-gray-600 dark:text-gray-300">
                {group.type === 'public' && 'Anyone can see and join this group.'}
                {group.type === 'private' && 'Only members can see posts. Admin approval required to join.'}
                {group.type === 'church_only' && 'Open to all church members.'}
              </p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
