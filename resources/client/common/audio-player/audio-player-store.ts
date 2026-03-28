import {create} from 'zustand';

interface PlayingSermon {
  id: number;
  title: string;
  speaker: string;
  audioUrl: string;
}

interface AudioPlayerState {
  currentSermon: PlayingSermon | null;
  isPlaying: boolean;
  play: (sermon: PlayingSermon) => void;
  pause: () => void;
  resume: () => void;
  stop: () => void;
}

export const useAudioPlayerStore = create<AudioPlayerState>(set => ({
  currentSermon: null,
  isPlaying: false,
  play: sermon => set({currentSermon: sermon, isPlaying: true}),
  pause: () => set({isPlaying: false}),
  resume: () => set({isPlaying: true}),
  stop: () => set({currentSermon: null, isPlaying: false}),
}));
