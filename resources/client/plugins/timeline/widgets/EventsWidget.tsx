import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Calendar, MapPin, Clock } from 'lucide-react';
import { format, isToday, isTomorrow, addDays } from 'date-fns';

interface Event {
  id: number;
  title: string;
  description: string;
  start_date: string;
  end_date: string;
  location?: string;
  is_all_day: boolean;
}

interface EventsWidgetProps {
  config?: {
    limit?: number;
    show_location?: boolean;
    days_ahead?: number;
  };
}

export function EventsWidget({ config = {} }: EventsWidgetProps) {
  const { limit = 5, show_location = true, days_ahead = 30 } = config;

  const { data: events = [], isLoading } = useQuery({
    queryKey: ['events', 'upcoming', { limit, days_ahead }],
    queryFn: async () => {
      const { data } = await apiClient.get(`/events?upcoming=true&limit=${limit}&days=${days_ahead}`);
      return data.data as Event[];
    },
  });

  const formatEventDate = (startDate: string, isAllDay: boolean) => {
    const date = new Date(startDate);
    
    if (isToday(date)) {
      return isAllDay ? 'Today' : `Today at ${format(date, 'h:mm a')}`;
    }
    if (isTomorrow(date)) {
      return isAllDay ? 'Tomorrow' : `Tomorrow at ${format(date, 'h:mm a')}`;
    }
    
    const daysDiff = Math.ceil((date.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));
    if (daysDiff <= 7) {
      return isAllDay 
        ? format(date, 'EEEE') 
        : format(date, 'EEEE \'at\' h:mm a');
    }
    
    return isAllDay 
      ? format(date, 'MMM d') 
      : format(date, 'MMM d \'at\' h:mm a');
  };

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-4">
      <div className="flex items-center gap-2 mb-4">
        <Calendar className="w-5 h-5 text-indigo-400" />
        <h3 className="text-lg font-semibold text-white">Upcoming Events</h3>
      </div>

      {isLoading ? (
        <div className="space-y-3">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="space-y-2">
              <div className="h-4 bg-gray-700 rounded animate-pulse w-3/4"></div>
              <div className="h-3 bg-gray-700 rounded animate-pulse w-1/2"></div>
            </div>
          ))}
        </div>
      ) : events.length > 0 ? (
        <div className="space-y-4">
          {events.map((event) => (
            <div 
              key={event.id}
              className="border-b border-white/5 last:border-0 pb-3 last:pb-0"
            >
              <h4 className="text-white font-medium text-sm mb-1 line-clamp-1">
                {event.title}
              </h4>
              
              <div className="space-y-1 text-xs text-gray-400">
                <div className="flex items-center gap-1">
                  <Clock className="w-3 h-3" />
                  {formatEventDate(event.start_date, event.is_all_day)}
                </div>
                
                {show_location && event.location && (
                  <div className="flex items-center gap-1">
                    <MapPin className="w-3 h-3" />
                    <span className="line-clamp-1">{event.location}</span>
                  </div>
                )}
              </div>
              
              {event.description && (
                <p className="text-gray-400 text-xs mt-1 line-clamp-2">
                  {event.description}
                </p>
              )}
            </div>
          ))}
        </div>
      ) : (
        <p className="text-gray-400 text-sm text-center py-4">
          No upcoming events
        </p>
      )}
    </div>
  );
}