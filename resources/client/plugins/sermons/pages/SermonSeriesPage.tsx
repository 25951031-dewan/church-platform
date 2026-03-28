import React, {useEffect, useRef} from 'react';
import {useParams} from 'react-router';
import {useSermonSeries, useSermons} from '../queries';
import {SermonCard} from '../components/SermonCard';

export function SermonSeriesPage() {
  const {seriesId} = useParams<{seriesId: string}>();
  const loaderRef = useRef<HTMLDivElement | null>(null);

  const {data: series, isLoading: seriesLoading} = useSermonSeries(seriesId!);
  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = useSermons({
    series_id: seriesId!,
  });

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

  if (seriesLoading) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Loading...</div>;
  }

  if (!series) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Series not found.</div>;
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      {series.image && (
        <div className="aspect-video rounded-xl overflow-hidden mb-6">
          <img src={series.image} alt={series.name} className="w-full h-full object-cover" />
        </div>
      )}

      <h1 className="text-2xl font-bold text-gray-900 mb-2">{series.name}</h1>
      {series.description && (
        <p className="text-gray-500 mb-6">{series.description}</p>
      )}
      <div className="text-sm text-gray-400 mb-6">{series.sermons_count} sermons</div>

      {isLoading ? (
        <div className="text-center py-8 text-gray-400">Loading sermons...</div>
      ) : allSermons.length === 0 ? (
        <div className="text-center py-8 text-gray-400">No sermons in this series.</div>
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
