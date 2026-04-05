import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Book } from 'lucide-react';

interface DailyVerse {
  verse: string;
  reference: string;
  version?: string;
}

interface DailyVerseWidgetProps {
  config?: {
    show_version?: boolean;
    text_size?: 'sm' | 'base' | 'lg';
  };
}

export function DailyVerseWidget({ config = {} }: DailyVerseWidgetProps) {
  const { show_version = true, text_size = 'base' } = config;

  const { data: dailyVerse, isLoading, error } = useQuery({
    queryKey: ['daily-verse'],
    queryFn: async () => {
      const { data } = await apiClient.get('/daily-verse');
      return data.verse as DailyVerse;
    },
    staleTime: 1000 * 60 * 60 * 12, // Cache for 12 hours
  });

  const textSizeClasses = {
    sm: 'text-sm',
    base: 'text-base',
    lg: 'text-lg'
  };

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-4">
      <div className="flex items-center gap-2 mb-3">
        <Book className="w-5 h-5 text-indigo-400" />
        <h3 className="text-lg font-semibold text-white">Daily Verse</h3>
      </div>

      {isLoading ? (
        <div className="space-y-2">
          <div className="h-4 bg-gray-700 rounded animate-pulse"></div>
          <div className="h-4 bg-gray-700 rounded animate-pulse w-3/4"></div>
          <div className="h-3 bg-gray-700 rounded animate-pulse w-1/2 mt-3"></div>
        </div>
      ) : error ? (
        <p className="text-gray-400 text-sm">Unable to load daily verse</p>
      ) : dailyVerse ? (
        <div className="space-y-3">
          <blockquote className={`${textSizeClasses[text_size]} text-white font-medium leading-relaxed italic`}>
            "{dailyVerse.verse}"
          </blockquote>
          <div className="text-right">
            <cite className="text-gray-400 text-sm font-medium">
              {dailyVerse.reference}
              {show_version && dailyVerse.version && (
                <span className="text-gray-500 ml-1">({dailyVerse.version})</span>
              )}
            </cite>
          </div>
        </div>
      ) : null}
    </div>
  );
}