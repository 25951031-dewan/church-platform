import type {Meeting} from '../types';
import {JoinMeetingButton} from './JoinMeetingButton';
import {LiveBadge} from './LiveBadge';

interface Props {
  meeting: Meeting;
}

export function MeetingCard({meeting}: Props) {
  return (
    <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
      <div className="mb-2 flex items-center justify-between">
        <p className="font-semibold text-gray-900 dark:text-white">{meeting.title}</p>
        {meeting.is_live && <LiveBadge />}
      </div>
      <p className="text-sm text-gray-500 dark:text-gray-400">
        {new Date(meeting.starts_at).toLocaleString()} • {meeting.platform.replace('_', ' ')}
      </p>
      {meeting.description && <p className="mt-2 text-sm text-gray-700 dark:text-gray-300">{meeting.description}</p>}
      <div className="mt-3">
        <JoinMeetingButton url={meeting.meeting_url} platform={meeting.platform} isLive={meeting.is_live} />
      </div>
    </div>
  );
}
