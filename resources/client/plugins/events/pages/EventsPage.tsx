import React, {useEffect, useRef, useState} from 'react';
import {useEvents} from '../queries';
import {EventCard} from '../components/EventCard';

type Tab = 'upcoming' | 'all' | 'featured';

export function EventsPage() {
  const [activeTab, setActiveTab] = useState<Tab>('upcoming');
  const loaderRef = useRef<HTMLDivElement | null>(null);

  const params: Record<string, string | boolean> =
    activeTab === 'upcoming'
      ? {upcoming: true}
      : activeTab === 'featured'
        ? {featured: true}
        : {};

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} =
    useEvents(params);

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

  const allEvents = data?.pages.flatMap((p: any) => p.data) ?? [];

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-white mb-6">Events</h1>

      <div className="flex gap-1 mb-6 border-b border-white/5">
        {(['upcoming', 'all', 'featured'] as Tab[]).map(tab => (
          <button
            key={tab}
            onClick={() => setActiveTab(tab)}
            className={`px-4 py-2 text-sm font-medium capitalize border-b-2 transition-colors ${
              activeTab === tab
                ? 'border-indigo-500 text-white'
                : 'border-transparent text-gray-500 hover:text-gray-300'
            }`}
          >
            {tab}
          </button>
        ))}
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Loading events...</div>
      ) : allEvents.length === 0 ? (
        <div className="text-center py-12 text-gray-500">No events found.</div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {allEvents.map((event: any) => (
            <EventCard key={event.id} event={event} />
          ))}
        </div>
      )}

      <div ref={loaderRef} className="py-4 text-center text-gray-600 text-sm">
        {isFetchingNextPage ? 'Loading more...' : ''}
      </div>
    </div>
  );
}
