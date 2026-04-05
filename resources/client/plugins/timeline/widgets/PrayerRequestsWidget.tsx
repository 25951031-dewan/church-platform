import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Heart, Clock } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';

interface PrayerRequest {
  id: number;
  title: string;
  description: string;
  is_anonymous: boolean;
  prayer_count: number;
  created_at: string;
  user?: {
    name: string;
    avatar?: string;
  };
}

interface PrayerRequestsWidgetProps {
  config?: {
    limit?: number;
    show_prayer_count?: boolean;
    show_author?: boolean;
  };
}

export function PrayerRequestsWidget({ config = {} }: PrayerRequestsWidgetProps) {
  const { limit = 5, show_prayer_count = true, show_author = true } = config;

  const { data: prayerRequests = [], isLoading } = useQuery({
    queryKey: ['prayer-requests', { limit }],
    queryFn: async () => {
      const { data } = await apiClient.get(`/prayer-requests?limit=${limit}`);
      return data.data as PrayerRequest[];
    },
  });

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-4">
      <div className="flex items-center gap-2 mb-4">
        <Heart className="w-5 h-5 text-indigo-400" />
        <h3 className="text-lg font-semibold text-white">Prayer Requests</h3>
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
      ) : prayerRequests.length > 0 ? (
        <div className="space-y-4">
          {prayerRequests.map((request) => (
            <div 
              key={request.id}
              className="border-b border-white/5 last:border-0 pb-3 last:pb-0"
            >
              <h4 className="text-white font-medium text-sm mb-1 line-clamp-2">
                {request.title}
              </h4>
              
              <p className="text-gray-400 text-xs mb-2 line-clamp-2">
                {request.description}
              </p>
              
              <div className="flex items-center justify-between text-xs text-gray-500">
                <div className="flex items-center gap-2">
                  {show_author && (
                    <span>
                      {request.is_anonymous ? 'Anonymous' : request.user?.name || 'Anonymous'}
                    </span>
                  )}
                  {show_prayer_count && (
                    <div className="flex items-center gap-1">
                      <Heart className="w-3 h-3 text-red-400 fill-current" />
                      <span>{request.prayer_count} prayers</span>
                    </div>
                  )}
                </div>
                
                <div className="flex items-center gap-1">
                  <Clock className="w-3 h-3" />
                  {formatDistanceToNow(new Date(request.created_at), { addSuffix: true })}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <p className="text-gray-400 text-sm text-center py-4">
          No prayer requests at this time
        </p>
      )}
    </div>
  );
}