import React, {useEffect, useRef, useState} from 'react';
import {useSermons, useSermonSeriesList, useSpeakers} from '../queries';
import {SermonCard} from '../components/SermonCard';

export function SermonsPage() {
  const [search, setSearch] = useState('');
  const [speakerId, setSpeakerId] = useState('');
  const [seriesId, setSeriesId] = useState('');
  const loaderRef = useRef<HTMLDivElement | null>(null);

  const params: Record<string, string | boolean> = {};
  if (search) params.search = search;
  if (speakerId) params.speaker_id = speakerId;
  if (seriesId) params.series_id = seriesId;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = useSermons(params);
  const {data: seriesList} = useSermonSeriesList();
  const {data: speakers} = useSpeakers();

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

  const allSermons = data?.pages.flatMap((p: any) => p.data) ?? [];

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Sermons</h1>

      <div className="flex flex-wrap gap-3 mb-6">
        <input
          type="search"
          placeholder="Search sermons..."
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        {speakers && speakers.length > 0 && (
          <select
            value={speakerId}
            onChange={e => setSpeakerId(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Speakers</option>
            {speakers.map((s: any) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        )}
        {seriesList && seriesList.length > 0 && (
          <select
            value={seriesId}
            onChange={e => setSeriesId(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Series</option>
            {seriesList.map((s: any) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        )}
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-400">Loading sermons...</div>
      ) : allSermons.length === 0 ? (
        <div className="text-center py-12 text-gray-400">No sermons found.</div>
      ) : (
        <div className="space-y-3">
          {allSermons.map((sermon: any) => (
            <SermonCard key={sermon.id} sermon={sermon} />
          ))}
        </div>
      )}

      <div ref={loaderRef} className="py-4 text-center text-gray-400 text-sm">
        {isFetchingNextPage ? 'Loading more...' : ''}
      </div>
    </div>
  );
}
