import React from 'react';
import {Link} from 'react-router';
import type {Event} from '../queries';

interface EventCardProps {
  event: Event;
}

export function EventCard({event}: EventCardProps) {
  const startDate = new Date(event.start_date);
  const hasMeetingUrl = Boolean(event.meeting_url);

  return (
    <div className="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
      {event.image && (
        <div className="aspect-video overflow-hidden">
          <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
        </div>
      )}
      <div className="p-4">
        <div className="flex items-start justify-between gap-2 mb-2">
          <Link
            to={`/events/${event.id}`}
            className="font-semibold text-gray-900 hover:text-blue-600 line-clamp-2"
          >
            {event.title}
          </Link>
          {hasMeetingUrl && (
            <span className="shrink-0 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
              Online
            </span>
          )}
        </div>

        <div className="text-sm text-gray-500 space-y-1 mb-3">
          <div className="flex items-center gap-1">
            <span>📅</span>
            <span>
              {startDate.toLocaleDateString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
              })}
            </span>
          </div>
          {event.location && (
            <div className="flex items-center gap-1">
              <span>📍</span>
              <span className="truncate">{event.location}</span>
            </div>
          )}
        </div>

        {event.attending_rsvps_count > 0 && (
          <div className="text-xs text-gray-400">
            {event.attending_rsvps_count} attending
          </div>
        )}
      </div>
    </div>
  );
}
