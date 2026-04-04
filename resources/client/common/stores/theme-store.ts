import {create} from 'zustand';
import {persist} from 'zustand/middleware';

type ThemeMode = 'light' | 'dark' | 'system';

interface ThemeState {
  mode: ThemeMode;
  resolvedTheme: 'light' | 'dark';
  
  // Actions
  setMode: (mode: ThemeMode) => void;
  toggle: () => void;
}

function getSystemTheme(): 'light' | 'dark' {
  if (typeof window === 'undefined') return 'light';
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function resolveTheme(mode: ThemeMode): 'light' | 'dark' {
  if (mode === 'system') return getSystemTheme();
  return mode;
}

function applyTheme(theme: 'light' | 'dark') {
  if (typeof document === 'undefined') return;
  document.documentElement.classList.remove('light', 'dark');
  document.documentElement.classList.add(theme);
}

export const useThemeStore = create<ThemeState>()(
  persist(
    (set, get) => ({
      mode: 'system',
      resolvedTheme: getSystemTheme(),
      
      setMode: (mode) => {
        const resolved = resolveTheme(mode);
        applyTheme(resolved);
        set({mode, resolvedTheme: resolved});
      },
      
      toggle: () => {
        const current = get().resolvedTheme;
        const newMode = current === 'light' ? 'dark' : 'light';
        applyTheme(newMode);
        set({mode: newMode, resolvedTheme: newMode});
      },
    }),
    {
      name: 'church-theme-storage',
      onRehydrateStorage: () => (state) => {
        // Apply theme on rehydration
        if (state) {
          const resolved = resolveTheme(state.mode);
          applyTheme(resolved);
          state.resolvedTheme = resolved;
        }
      },
    }
  )
);

// Listen for system theme changes
if (typeof window !== 'undefined') {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const state = useThemeStore.getState();
    if (state.mode === 'system') {
      const resolved = e.matches ? 'dark' : 'light';
      applyTheme(resolved);
      useThemeStore.setState({resolvedTheme: resolved});
    }
  });
}
