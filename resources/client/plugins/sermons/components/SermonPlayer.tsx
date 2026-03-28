import React from 'react';
import {useAudioPlayerStore} from '@app/common/audio-player/audio-player-store';
import type {Sermon} from '../queries';

interface SermonPlayerProps {
  sermon: Sermon;
}

export function SermonPlayer({sermon}: SermonPlayerProps) {
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

  if (sermon.video_url) {
    return (
      <div className="aspect-video rounded-xl overflow-hidden bg-black mb-6">
        <iframe
          src={sermon.video_url.replace('watch?v=', 'embed/')}
          title={sermon.title}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
          className="w-full h-full"
        />
      </div>
    );
  }

  if (sermon.audio_url) {
    return (
      <div className="flex items-center gap-4 p-4 bg-gray-50 rounded-xl mb-6">
        <button
          onClick={handlePlay}
          className="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-colors text-lg"
        >
          {isCurrentlyPlaying ? '⏸' : '▶'}
        </button>
        <div>
          <div className="text-sm font-medium text-gray-900">
            {isCurrentlyPlaying ? 'Now Playing' : 'Listen to Sermon'}
          </div>
          {sermon.duration_minutes && (
            <div className="text-xs text-gray-400">{sermon.duration_minutes} minutes</div>
          )}
        </div>
      </div>
    );
  }

  return null;
}
