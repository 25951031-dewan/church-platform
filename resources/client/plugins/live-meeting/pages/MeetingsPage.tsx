import {useNavigate} from 'react-router';
import {useMeetings, useLiveMeetings, Meeting, MeetingPlatform} from '../queries';

const PLATFORM_LABELS: Record<MeetingPlatform, string> = {
  zoom: 'Zoom',
  google_meet: 'Google Meet',
  youtube: 'YouTube',
  other: 'Other',
};

const PLATFORM_COLORS: Record<MeetingPlatform, string> = {
  zoom: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
  google_meet: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
  youtube: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
  other: 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
};

function formatMeetingTime(startsAt: string, timezone: string): string {
  return new Date(startsAt).toLocaleString('en-US', {
    timeZone: timezone,
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

interface MeetingCardProps {
  meeting: Meeting;
  onClick: (meeting: Meeting) => void;
  isLive?: boolean;
}

function MeetingCard({meeting, onClick, isLive}: MeetingCardProps) {
  return (
    <div
      className={`bg-white dark:bg-gray-800 rounded-lg border p-4 cursor-pointer hover:shadow-md transition-shadow ${
        isLive
          ? 'border-red-300 dark:border-red-700 ring-1 ring-red-200 dark:ring-red-800'
          : 'border-gray-200 dark:border-gray-700'
      }`}
      onClick={() => onClick(meeting)}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            {isLive && (
              <span className="inline-flex items-center gap-1 text-xs font-semibold text-red-600 dark:text-red-400">
                <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
                Live Now
              </span>
            )}
            <span
              className={`text-xs font-medium px-2 py-0.5 rounded ${PLATFORM_COLORS[meeting.platform]}`}
            >
              {PLATFORM_LABELS[meeting.platform]}
            </span>
          </div>
          <h3 className="font-semibold text-gray-900 dark:text-white truncate">{meeting.title}</h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            {formatMeetingTime(meeting.starts_at, meeting.timezone)}
          </p>
          {meeting.host && (
            <p className="text-xs text-gray-400 mt-1">Host: {meeting.host.name}</p>
          )}
        </div>

        <a
          href={meeting.meeting_url}
          target="_blank"
          rel="noopener noreferrer"
          onClick={e => e.stopPropagation()}
          className={`shrink-0 px-3 py-1.5 text-sm font-medium rounded-lg transition-colors ${
            isLive
              ? 'bg-red-600 text-white hover:bg-red-700'
              : 'bg-primary-600 text-white hover:bg-primary-700'
          }`}
        >
          Join
        </a>
      </div>
    </div>
  );
}

export function MeetingsPage() {
  const navigate = useNavigate();
  const {data: liveMeetings} = useLiveMeetings();
  const {data, isLoading, fetchNextPage, hasNextPage, isFetchingNextPage} = useMeetings();

  const allUpcoming = data?.pages.flatMap((page: any) => page.pagination?.data ?? page.data ?? []) ?? [];
  const upcoming = allUpcoming.filter((m: Meeting) => !m.is_live);

  function handleClick(meeting: Meeting) {
    navigate(`/meetings/${meeting.id}`);
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">Live Meetings</h1>

      {/* Live Now section */}
      {liveMeetings && liveMeetings.length > 0 && (
        <section className="mb-8">
          <h2 className="text-sm font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
            Live Now
          </h2>
          <div className="space-y-3">
            {liveMeetings.map(meeting => (
              <MeetingCard key={meeting.id} meeting={meeting} onClick={handleClick} isLive />
            ))}
          </div>
        </section>
      )}

      {/* Upcoming section */}
      <section>
        <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
          Upcoming
        </h2>

        {isLoading ? (
          <div className="text-center py-8 text-gray-500">Loading meetings...</div>
        ) : upcoming.length === 0 ? (
          <div className="text-center py-8 text-gray-500">No upcoming meetings.</div>
        ) : (
          <>
            <div className="space-y-3">
              {upcoming.map((meeting: Meeting) => (
                <MeetingCard key={meeting.id} meeting={meeting} onClick={handleClick} />
              ))}
            </div>

            {hasNextPage && (
              <div className="flex justify-center mt-6">
                <button
                  onClick={() => fetchNextPage()}
                  disabled={isFetchingNextPage}
                  className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium"
                >
                  {isFetchingNextPage ? 'Loading...' : 'Load More'}
                </button>
              </div>
            )}
          </>
        )}
      </section>
    </div>
  );
}
