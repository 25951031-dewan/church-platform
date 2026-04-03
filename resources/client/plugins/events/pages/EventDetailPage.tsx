import React from 'react';
import {useParams} from 'react-router';
import {useEvent} from '../queries';
import {RsvpButton} from '../components/RsvpButton';
import {ReactionBar} from '@app/common/components/ReactionBar';
import {CommentThread} from '@app/common/components/CommentThread';

export function EventDetailPage() {
  const {eventId} = useParams<{eventId: string}>();
  const {data: event, isLoading} = useEvent(eventId!);

  if (isLoading) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Loading...</div>;
  }

  if (!event) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Event not found.</div>;
  }

  const startDate = new Date(event.start_date);
  const endDate = event.end_date ? new Date(event.end_date) : null;
  const rsvpCounts = event.rsvp_counts ?? {};

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      {event.image && (
        <div className="aspect-video rounded-xl overflow-hidden mb-6">
          <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
        </div>
      )}

      <h1 className="text-3xl font-bold text-gray-900 mb-4">{event.title}</h1>

      <div className="flex flex-wrap gap-4 text-sm text-gray-500 mb-6">
        <div className="flex items-center gap-1">
          <span>📅</span>
          <span>
            {startDate.toLocaleDateString(undefined, {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
            })}
            {endDate && ` — ${endDate.toLocaleTimeString(undefined, {hour: '2-digit', minute: '2-digit'})}`}
          </span>
        </div>
        {event.location && (
          <div className="flex items-center gap-1">
            <span>📍</span>
            {event.location_url ? (
              <a href={event.location_url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                {event.location}
              </a>
            ) : (
              <span>{event.location}</span>
            )}
          </div>
        )}
      </div>

      {event.meeting_url && (
        <div className="mb-6">
          <a
            href={event.meeting_url}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <span>🎥</span>
            <span>Join Meeting</span>
          </a>
        </div>
      )}

      {/* RSVP */}
      <div className="flex items-center gap-6 mb-6 p-4 bg-gray-50 rounded-lg">
        <RsvpButton eventId={event.id} currentStatus={event.current_user_rsvp} />
        <div className="flex gap-4 text-sm text-gray-500">
          {rsvpCounts.attending > 0 && <span>{rsvpCounts.attending} attending</span>}
          {rsvpCounts.interested > 0 && <span>{rsvpCounts.interested} interested</span>}
        </div>
      </div>

      <div className="prose prose-gray max-w-none mb-8 whitespace-pre-wrap">
        {event.description}
      </div>

      <ReactionBar 
        reactableId={event.id} 
        reactableType="event"
        reactionCounts={event.reactions || {}}
        currentUserReaction={null}
        queryKey={['events', String(event.id)]}
      />

      <div className="mt-8">
        <CommentThread commentableId={event.id} commentableType="event" />
      </div>
    </div>
  );
}
