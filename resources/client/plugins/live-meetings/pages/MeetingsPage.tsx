import {useMemo} from 'react';
import {MeetingCard} from '../components/MeetingCard';
import {useLiveMeetings, useMeetings} from '../hooks';

export function MeetingsPage() {
  const {data: live = []} = useLiveMeetings();
  const {data, fetchNextPage, hasNextPage} = useMeetings();

  const meetings = useMemo(
    () => data?.pages.flatMap(page => page.pagination?.data ?? []) ?? [],
    [data],
  );

  return (
    <div className="mx-auto max-w-4xl space-y-6 px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Live Meetings</h1>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase text-red-600">Live now</h2>
        <div className="space-y-3">
          {live.length ? live.map(meeting => <MeetingCard key={meeting.id} meeting={meeting} />) : <p className="text-sm text-gray-500">No meetings live right now.</p>}
        </div>
      </section>

      <section>
        <h2 className="mb-3 text-sm font-semibold uppercase text-gray-500">Upcoming</h2>
        <div className="space-y-3">
          {meetings.filter(m => !m.is_live).map(meeting => <MeetingCard key={meeting.id} meeting={meeting} />)}
        </div>

        {hasNextPage && (
          <button className="mt-4 rounded-md bg-primary-600 px-4 py-2 text-sm text-white" onClick={() => fetchNextPage()}>
            Load more
          </button>
        )}
      </section>
    </div>
  );
}
