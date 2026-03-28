import {useEffect, useRef, useState} from 'react';
import {Link} from 'react-router';
import {usePrayerWall} from '../queries';
import {PrayerCard} from '../components/PrayerCard';
import type {PrayerRequest} from '../queries';

const CATEGORIES = [
  {value: '', label: 'All'},
  {value: 'health', label: 'Health'},
  {value: 'family', label: 'Family'},
  {value: 'financial', label: 'Financial'},
  {value: 'spiritual', label: 'Spiritual'},
  {value: 'relationships', label: 'Relationships'},
  {value: 'work', label: 'Work'},
  {value: 'grief', label: 'Grief'},
  {value: 'other', label: 'Other'},
];

export function PrayerWallPage() {
  const [category, setCategory] = useState('');
  const loaderRef = useRef<HTMLDivElement | null>(null);

  const params: Record<string, string> = {};
  if (category) params.category = category;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = usePrayerWall(params);

  useEffect(() => {
    const el = loaderRef.current;
    if (!el) return;
    const observer = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage();
        }
      },
      {threshold: 0.1}
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  const allPrayers: PrayerRequest[] = data?.pages.flatMap((p: any) => p.data) ?? [];

  return (
    <div className="max-w-2xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Prayer Wall</h1>
        <Link
          to="/prayers/submit"
          className="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700"
        >
          Submit Prayer
        </Link>
      </div>

      {/* Category filter */}
      <div className="flex gap-2 overflow-x-auto pb-3 mb-4 scrollbar-hide">
        {CATEGORIES.map(cat => (
          <button
            key={cat.value}
            onClick={() => setCategory(cat.value)}
            className={`px-3 py-1.5 rounded-full text-sm whitespace-nowrap transition-colors ${
              category === cat.value
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
            }`}
          >
            {cat.label}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-400">Loading prayers...</div>
      ) : allPrayers.length === 0 ? (
        <div className="text-center py-12 text-gray-400">
          No prayer requests yet. Be the first to share.
        </div>
      ) : (
        <div className="space-y-3">
          {allPrayers.map((prayer: PrayerRequest) => (
            <PrayerCard key={prayer.id} prayer={prayer} />
          ))}
        </div>
      )}

      <div ref={loaderRef} className="py-4 text-center text-gray-400 text-sm">
        {isFetchingNextPage ? 'Loading more...' : ''}
      </div>
    </div>
  );
}
