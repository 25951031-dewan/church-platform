import {useUpcomingMeetings} from '../hooks';

export function UpcomingMeetingsWidget() {
  const {meetings} = useUpcomingMeetings();

  return (
    <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
      <h3 className="mb-2 text-sm font-semibold uppercase text-gray-500">Upcoming Meetings</h3>
      <div className="space-y-2">
        {meetings.slice(0, 5).map(meeting => (
          <div key={meeting.id} className="text-sm">
            <p className="font-medium text-gray-900 dark:text-white">{meeting.title}</p>
            <p className="text-xs text-gray-500">{new Date(meeting.starts_at).toLocaleString()}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
