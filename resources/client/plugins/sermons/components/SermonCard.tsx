import React from 'react';
import {Link} from 'react-router';
import {useAudioPlayerStore} from '@app/common/audio-player/audio-player-store';
import type {Sermon} from '../queries';

interface SermonCardProps {
  sermon: Sermon;
}

export function SermonCard({sermon}: SermonCardProps) {
  const {play, currentSermon, isPlaying, pause} = useAudioPlayerStore();

  const isCurrentlyPlaying = currentSermon?.id === sermon.id && isPlaying;

  const handlePlay = () => {
    if (!sermon.audio_url) return;
    if (isCurrentlyPlaying) {
      pause();
    } else {
      play({
        id: sermon.id,
        title: sermon.title,
        speaker: sermon.speaker,
        audioUrl: sermon.audio_url,
      });
    }
  };

  return (
    <div className="bg-[#161920] rounded-lg border border-white/5 p-4 hover:border-white/10 transition-colors">
      <div className="flex gap-3">
        {sermon.image && (
          <div className="shrink-0 w-16 h-16 rounded-lg overflow-hidden">
            <img src={sermon.image} alt={sermon.title} className="w-full h-full object-cover" />
          </div>
        )}
        <div className="flex-1 min-w-0">
          <Link
            to={`/sermons/${sermon.id}`}
            className="font-semibold text-white hover:text-indigo-400 line-clamp-2 block mb-1 transition-colors"
          >
            {sermon.title}
          </Link>
          <div className="text-sm text-gray-400 mb-1">{sermon.speaker}</div>
          <div className="flex flex-wrap gap-2 text-xs text-gray-500">
            {sermon.scripture_reference && <span>{sermon.scripture_reference}</span>}
            {sermon.sermon_date && <span>{new Date(sermon.sermon_date).toLocaleDateString()}</span>}
            {sermon.duration_minutes && <span>{sermon.duration_minutes}m</span>}
          </div>
          {sermon.sermon_series && (
            <Link
              to={`/sermon-series/${sermon.sermon_series.id}`}
              className="inline-block mt-1 text-xs bg-indigo-500/10 text-indigo-400 px-2 py-0.5 rounded-full hover:bg-indigo-500/20"
            >
              {sermon.sermon_series.name}
            </Link>
          )}
        </div>
        {sermon.audio_url && (
          <button
            onClick={handlePlay}
            className="shrink-0 self-center w-10 h-10 rounded-full bg-indigo-500/10 text-indigo-400 flex items-center justify-center hover:bg-indigo-500/20 transition-colors"
          >
            {isCurrentlyPlaying ? '⏸' : '▶'}
          </button>
        )}
      </div>
    </div>
  );
}
