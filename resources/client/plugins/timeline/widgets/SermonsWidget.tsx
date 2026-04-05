import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Play, Clock, User } from 'lucide-react';
import { format } from 'date-fns';

interface Sermon {
  id: number;
  title: string;
  description: string;
  sermon_date: string;
  duration?: string;
  audio_file?: string;
  video_file?: string;
  speaker?: {
    name: string;
    title?: string;
  };
  series?: {
    name: string;
  };
}

interface SermonsWidgetProps {
  config?: {
    limit?: number;
    show_speaker?: boolean;
    show_duration?: boolean;
    show_series?: boolean;
  };
}

export function SermonsWidget({ config = {} }: SermonsWidgetProps) {
  const { limit = 5, show_speaker = true, show_duration = true, show_series = false } = config;

  const { data: sermons = [], isLoading } = useQuery({
    queryKey: ['sermons', { limit }],
    queryFn: async () => {
      const { data } = await apiClient.get(`/sermons?limit=${limit}`);
      return data.data as Sermon[];
    },
  });

  const hasMedia = (sermon: Sermon) => sermon.audio_file || sermon.video_file;

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-4">
      <div className="flex items-center gap-2 mb-4">
        <Play className="w-5 h-5 text-indigo-400" />
        <h3 className="text-lg font-semibold text-white">Recent Sermons</h3>
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
      ) : sermons.length > 0 ? (
        <div className="space-y-4">
          {sermons.map((sermon) => (
            <div 
              key={sermon.id}
              className="border-b border-white/5 last:border-0 pb-3 last:pb-0"
            >
              <div className="flex items-start gap-2 mb-1">
                <h4 className="text-white font-medium text-sm line-clamp-2 flex-1">
                  {sermon.title}
                </h4>
                {hasMedia(sermon) && (
                  <Play className="w-4 h-4 text-indigo-400 flex-shrink-0 mt-0.5" />
                )}
              </div>
              
              {show_series && sermon.series && (
                <p className="text-indigo-400 text-xs mb-1 font-medium">
                  {sermon.series.name}
                </p>
              )}
              
              <div className="flex items-center justify-between text-xs text-gray-500">
                <div className="flex items-center gap-2">
                  {show_speaker && sermon.speaker && (
                    <div className="flex items-center gap-1">
                      <User className="w-3 h-3" />
                      <span>{sermon.speaker.name}</span>
                    </div>
                  )}
                  
                  {show_duration && sermon.duration && (
                    <div className="flex items-center gap-1">
                      <Clock className="w-3 h-3" />
                      <span>{sermon.duration}</span>
                    </div>
                  )}
                </div>
                
                <span>
                  {format(new Date(sermon.sermon_date), 'MMM d, yyyy')}
                </span>
              </div>
              
              {sermon.description && (
                <p className="text-gray-400 text-xs mt-1 line-clamp-2">
                  {sermon.description}
                </p>
              )}
            </div>
          ))}
        </div>
      ) : (
        <p className="text-gray-400 text-sm text-center py-4">
          No sermons available
        </p>
      )}
    </div>
  );
}