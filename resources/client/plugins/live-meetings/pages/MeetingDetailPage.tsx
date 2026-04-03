import {useEffect, useState} from 'react';
import {useNavigate, useParams} from 'react-router';
import {JoinMeetingButton, LiveBadge} from '../components';
import {useMeeting, useRegisterForMeeting, useUnregisterForMeeting} from '../hooks';

function useCountdown(startsAt?: string): string | null {
  const [timeLeft, setTimeLeft] = useState<string | null>(null);

  useEffect(() => {
    if (!startsAt) return;

    const update = () => {
      const diff = new Date(startsAt).getTime() - Date.now();
      if (diff <= 0) {
        setTimeLeft(null);
        return;
      }
      const hours = Math.floor(diff / 3_600_000);
      const minutes = Math.floor((diff % 3_600_000) / 60_000);
      const seconds = Math.floor((diff % 60_000) / 1_000);
      setTimeLeft(hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m ${seconds}s`);
    };

    update();
    const id = setInterval(update, 1_000);
    return () => clearInterval(id);
  }, [startsAt]);

  return timeLeft;
}

export function MeetingDetailPage() {
  const {meetingId} = useParams<{meetingId: string}>();
  const navigate = useNavigate();
  const {data: meeting, isLoading} = useMeeting(meetingId || '');

  const register = useRegisterForMeeting(Number(meetingId));
  const unregister = useUnregisterForMeeting(Number(meetingId));

  const countdown = useCountdown(meeting?.starts_at);

  if (isLoading || !meeting) {
    return <div className="flex h-64 items-center justify-center">Loading...</div>;
  }

  return (
    <div className="mx-auto max-w-2xl px-4 py-6">
      <button
        onClick={() => navigate('/meetings')}
        className="mb-6 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
      >
        &larr; Back to meetings
      </button>

      {meeting.is_live && (
        <div className="mb-3">
          <LiveBadge />
        </div>
      )}

      <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{meeting.title}</h1>

      {meeting.description && <p className="mt-4 text-gray-700 dark:text-gray-300">{meeting.description}</p>}

      <div className="mt-4 space-y-1 text-sm text-gray-600 dark:text-gray-400">
        <p><strong>Starts:</strong> {new Date(meeting.starts_at).toLocaleString()}</p>
        <p><strong>Ends:</strong> {new Date(meeting.ends_at).toLocaleString()}</p>
        <p><strong>Platform:</strong> {meeting.platform.replace('_', ' ')}</p>
        {meeting.host && <p><strong>Host:</strong> {meeting.host.name}</p>}
      </div>

      {countdown && !meeting.is_live && (
        <div className="mt-6 rounded-xl bg-primary-50 p-4 text-center dark:bg-primary-900/20">
          <p className="text-sm text-primary-600">Starts in</p>
          <p className="mt-1 text-3xl font-bold text-primary-700 dark:text-primary-300">{countdown}</p>
        </div>
      )}

      <div className="mt-6 flex flex-wrap gap-2">
        <JoinMeetingButton url={meeting.meeting_url} platform={meeting.platform} isLive={meeting.is_live} />

        {meeting.requires_registration && (
          <>
            <button
              className="rounded-lg border border-primary-600 px-4 py-2 text-sm text-primary-600"
              onClick={() => register.mutate()}
            >
              Register
            </button>
            <button
              className="rounded-lg border border-gray-400 px-4 py-2 text-sm text-gray-600"
              onClick={() => unregister.mutate()}
            >
              Unregister
            </button>
          </>
        )}
      </div>
    </div>
  );
}
