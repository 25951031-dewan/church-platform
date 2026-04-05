import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Megaphone, Clock } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';

interface Announcement {
  id: number;
  title: string;
  content: string;
  priority: 'low' | 'normal' | 'high';
  created_at: string;
  user?: {
    name: string;
    avatar?: string;
  };
}

interface AnnouncementsWidgetProps {
  config?: {
    limit?: number;
    show_priority?: boolean;
    show_author?: boolean;
  };
}

export function AnnouncementsWidget({ config = {} }: AnnouncementsWidgetProps) {
  const { limit = 5, show_priority = true, show_author = true } = config;

  const { data: announcements = [], isLoading } = useQuery({
    queryKey: ['posts', 'announcements', { limit }],
    queryFn: async () => {
      const { data } = await apiClient.get(`/posts?type=announcement&limit=${limit}`);
      return data.data as Announcement[];
    },
  });

  const priorityColors = {
    low: 'text-gray-400',
    normal: 'text-blue-400',
    high: 'text-red-400'
  };

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-4">
      <div className="flex items-center gap-2 mb-4">
        <Megaphone className="w-5 h-5 text-indigo-400" />
        <h3 className="text-lg font-semibold text-white">Announcements</h3>
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
      ) : announcements.length > 0 ? (
        <div className="space-y-4">
          {announcements.map((announcement) => (
            <div 
              key={announcement.id}
              className="border-b border-white/5 last:border-0 pb-3 last:pb-0"
            >
              <div className="flex items-start justify-between gap-2 mb-2">
                <h4 className="text-white font-medium text-sm line-clamp-2 flex-1">
                  {announcement.title}
                </h4>
                {show_priority && (
                  <span className={`text-xs font-semibold uppercase ${priorityColors[announcement.priority]}`}>
                    {announcement.priority}
                  </span>
                )}
              </div>
              
              <p className="text-gray-400 text-sm line-clamp-2 mb-2">
                {announcement.content}
              </p>
              
              <div className="flex items-center justify-between text-xs text-gray-500">
                {show_author && announcement.user && (
                  <span>{announcement.user.name}</span>
                )}
                <div className="flex items-center gap-1">
                  <Clock className="w-3 h-3" />
                  {formatDistanceToNow(new Date(announcement.created_at), { addSuffix: true })}
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <p className="text-gray-400 text-sm text-center py-4">
          No announcements at this time
        </p>
      )}
    </div>
  );
}