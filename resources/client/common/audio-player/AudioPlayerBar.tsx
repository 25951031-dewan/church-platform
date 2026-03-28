import React, {useEffect, useRef, useState} from 'react';
import {useAudioPlayerStore} from './audio-player-store';

export function AudioPlayerBar() {
  const {currentSermon, isPlaying, pause, resume, stop} = useAudioPlayerStore();
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const [progress, setProgress] = useState(0);
  const [duration, setDuration] = useState(0);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;
    if (isPlaying) {
      audio.play().catch(() => pause());
    } else {
      audio.pause();
    }
  }, [isPlaying, pause]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio || !currentSermon) return;
    audio.src = currentSermon.audioUrl;
    audio.play().catch(() => pause());
  }, [currentSermon?.id, pause]);

  const handleTimeUpdate = () => {
    const audio = audioRef.current;
    if (audio && audio.duration) {
      setProgress(audio.currentTime);
      setDuration(audio.duration);
    }
  };

  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.currentTime = Number(e.target.value);
  };

  const formatTime = (seconds: number) => {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
  };

  if (!currentSermon) return null;

  return (
    <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 px-4 py-3">
      <audio
        ref={audioRef}
        onTimeUpdate={handleTimeUpdate}
        onLoadedMetadata={handleTimeUpdate}
        onEnded={stop}
      />
      <div className="max-w-4xl mx-auto flex items-center gap-4">
        <button
          onClick={isPlaying ? pause : resume}
          className="shrink-0 w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition-colors"
        >
          {isPlaying ? '⏸' : '▶'}
        </button>

        <div className="flex-1 min-w-0">
          <div className="text-sm font-medium text-gray-900 truncate">{currentSermon.title}</div>
          <div className="text-xs text-gray-500 truncate">{currentSermon.speaker}</div>
          <div className="flex items-center gap-2 mt-1">
            <span className="text-xs text-gray-400 w-10 text-right">{formatTime(progress)}</span>
            <input
              type="range"
              min={0}
              max={duration || 100}
              value={progress}
              onChange={handleSeek}
              className="flex-1 h-1 accent-blue-600"
            />
            <span className="text-xs text-gray-400 w-10">{formatTime(duration)}</span>
          </div>
        </div>

        <button
          onClick={stop}
          className="shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
          title="Close player"
        >
          ✕
        </button>
      </div>
    </div>
  );
}
