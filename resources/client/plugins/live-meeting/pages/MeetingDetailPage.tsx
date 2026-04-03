import {useEffect, useState} from 'react';
import {useParams, useNavigate} from 'react-router';
import {useMeeting, MeetingPlatform} from '../queries';

const PLATFORM_LABELS: Record<MeetingPlatform, string> = {
  zoom: 'Zoom',
  google_meet: 'Google Meet',
  youtube: 'YouTube',
  other: 'Other',
};

function useCountdown(startsAt: string): string | null {
  const [timeLeft, setTimeLeft] = useState<string | null>(null);

  useEffect(() => {
    function compute() {
      const diff = new Date(startsAt).getTime() - Date.now();
      if (diff <= 0) {
        setTimeLeft(null);
        return;
      }
      const hours = Math.floor(diff / 3_600_000);
      const minutes = Math.floor((diff % 3_600_000) / 60_000);
      const seconds = Math.floor((diff % 60_000) / 1_000);
      setTimeLeft(
        hours > 0
          ? `${hours}h ${minutes}m`
          : minutes > 0
          ? `${minutes}m ${seconds}s`
          : `${seconds}s`
      );
    }
    compute();
    const id = setInterval(compute, 1_000);
    return () => clearInterval(id);
  }, [startsAt]);

  return timeLeft;
}

export function MeetingDetailPage() {
  const {meetingId} = useParams<{meetingId: string}>();
  const navigate = useNavigate();
  const {data: meeting, isLoading} = useMeeting(meetingId!);
  const countdown = useCountdown(meeting?.starts_at ?? new Date(0).toISOString());

  if (isLoading || !meeting) {
    return <div className="flex items-center justify-center h-64">Loading...</div>;
  }

  const startFormatted = new Date(meeting.starts_at).toLocaleString('en-US', {
    timeZone: meeting.timezone,
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });

  const endFormatted = new Date(meeting.ends_at).toLocaleString('en-US', {
    timeZone: meeting.timezone,
    hour: 'numeric',
    minute: '2-digit',
    timeZoneName: 'short',
  });

  return (
    <div className="max-w-2xl mx-auto px-4 py-6">
      <button
        onClick={() => navigate('/meetings')}
        className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6 inline-flex items-center gap-1"
      >
        &larr; Back to Meetings
      </button>

      {/* Cover image */}
      {meeting.cover_image && (
        <div className="aspect-video rounded-xl overflow-hidden mb-6">
          <img src={meeting.cover_image} alt={meeting.title} className="w-full h-full object-cover" />
        </div>
      )}

      {/* Live badge */}
      {meeting.is_live && (
        <div className="flex items-center gap-2 mb-3 text-red-600 dark:text-red-400">
          <span className="w-3 h-3 rounded-full bg-red-500 animate-pulse" />
          <span className="text-sm font-semibold">Live Now</span>
        </div>
      )}

      <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{meeting.title}</h1>

      {/* Meta */}
      <div className="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
        <div className="flex items-center gap-2">
          <span className="font-medium">Platform:</span>
          <span>{PLATFORM_LABELS[meeting.platform]}</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="font-medium">Starts:</span>
          <span>{startFormatted}</span>
        </div>
        <div className="flex items-center gap-2">
          <span className="font-medium">Ends:</span>
          <span>{endFormatted}</span>
        </div>
        {meeting.host && (
          <div className="flex items-center gap-2">
            <span className="font-medium">Host:</span>
            <span>{meeting.host.name}</span>
          </div>
        )}
        {meeting.is_recurring && meeting.recurrence_rule && (
          <div className="flex items-center gap-2">
            <span className="font-medium">Recurs:</span>
            <span className="capitalize">{meeting.recurrence_rule}</span>
          </div>
        )}
      </div>

      {/* Description */}
      {meeting.description && (
        <p className="mt-5 text-gray-700 dark:text-gray-300 leading-relaxed">{meeting.description}</p>
      )}

      {/* Countdown */}
      {countdown && !meeting.is_live && (
        <div className="mt-6 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-xl text-center">
          <p className="text-sm text-primary-600 dark:text-primary-400 font-medium">Starts in</p>
          <p className="text-3xl font-bold text-primary-700 dark:text-primary-300 tabular-nums mt-1">
            {countdown}
          </p>
        </div>
      )}

      {/* Join button */}
      <a
        href={meeting.meeting_url}
        target="_blank"
        rel="noopener noreferrer"
        className={`mt-6 flex items-center justify-center gap-2 w-full py-3 px-6 rounded-xl text-white font-semibold text-lg transition-colors ${
          meeting.is_live
            ? 'bg-red-600 hover:bg-red-700'
            : 'bg-primary-600 hover:bg-primary-700'
        }`}
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
        </svg>
        Join Meeting
      </a>
    </div>
  );
}
