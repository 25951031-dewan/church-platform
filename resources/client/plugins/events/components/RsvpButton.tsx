import React from 'react';
import {useCancelRsvp, useRsvp} from '../queries';

type RsvpStatus = 'attending' | 'interested' | 'not_going';

interface RsvpButtonProps {
  eventId: number;
  currentStatus: RsvpStatus | null;
}

const statusConfig: Record<
  RsvpStatus,
  {label: string; activeClass: string; icon: string}
> = {
  attending: {label: 'Attending', activeClass: 'bg-green-100 text-green-700 border-green-300', icon: '✓'},
  interested: {label: 'Interested', activeClass: 'bg-blue-100 text-blue-700 border-blue-300', icon: '★'},
  not_going: {label: "Can't Go", activeClass: 'bg-gray-100 text-gray-600 border-gray-300', icon: '✗'},
};

export function RsvpButton({eventId, currentStatus}: RsvpButtonProps) {
  const rsvp = useRsvp(eventId);
  const cancelRsvp = useCancelRsvp(eventId);

  const handleClick = (status: RsvpStatus) => {
    if (currentStatus === status) {
      cancelRsvp.mutate();
    } else {
      rsvp.mutate(status);
    }
  };

  const isPending = rsvp.isPending || cancelRsvp.isPending;

  return (
    <div className="flex gap-2">
      {(Object.keys(statusConfig) as RsvpStatus[]).map(status => {
        const config = statusConfig[status];
        const isActive = currentStatus === status;
        return (
          <button
            key={status}
            onClick={() => handleClick(status)}
            disabled={isPending}
            className={`flex items-center gap-1 px-3 py-1.5 text-sm border rounded-full transition-colors disabled:opacity-50 ${
              isActive
                ? config.activeClass
                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
            }`}
          >
            <span>{config.icon}</span>
            <span>{config.label}</span>
          </button>
        );
      })}
    </div>
  );
}
