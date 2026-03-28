import {useState, useRef, useCallback} from 'react';
import {useGroups} from '../queries';
import {GroupCard} from '../components/GroupCard';

type Tab = 'discover' | 'my_groups' | 'featured';

export function GroupBrowserPage() {
  const [tab, setTab] = useState<Tab>('discover');
  const [search, setSearch] = useState('');

  const params: Record<string, string | boolean> = {};
  if (tab === 'my_groups') params.my_groups = true;
  if (tab === 'featured') params.featured = true;
  if (search) params.search = search;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} =
    useGroups(params);

  const observer = useRef<IntersectionObserver | null>(null);
  const lastRef = useCallback(
    (node: HTMLDivElement | null) => {
      if (isFetchingNextPage) return;
      if (observer.current) observer.current.disconnect();
      observer.current = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting && hasNextPage) {
          fetchNextPage();
        }
      });
      if (node) observer.current.observe(node);
    },
    [isFetchingNextPage, hasNextPage, fetchNextPage]
  );

  const groups = data?.pages.flatMap((p: any) => p.data) ?? [];

  const tabs: {key: Tab; label: string}[] = [
    {key: 'discover', label: 'Discover'},
    {key: 'my_groups', label: 'My Groups'},
    {key: 'featured', label: 'Featured'},
  ];

  return (
    <div className="max-w-5xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Groups</h1>
      </div>
      <div className="flex gap-2 mb-4">
        {tabs.map(t => (
          <button
            key={t.key}
            onClick={() => setTab(t.key)}
            className={`px-4 py-2 rounded-md text-sm font-medium ${
              tab === t.key
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>
      <input
        type="text"
        placeholder="Search groups..."
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="w-full px-4 py-2 mb-6 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
      />
      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Loading groups...</div>
      ) : groups.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No groups found</div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {groups.map((group: any, i: number) => (
            <div key={group.id} ref={i === groups.length - 1 ? lastRef : undefined}>
              <GroupCard group={group} />
            </div>
          ))}
        </div>
      )}
      {isFetchingNextPage && (
        <div className="text-center py-4 text-gray-500">Loading more...</div>
      )}
    </div>
  );
}
