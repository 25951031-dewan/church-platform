import {create} from 'zustand';
import {persist} from 'zustand/middleware';

interface AppState {
  // Sidebar state
  sidebarCollapsed: boolean;
  mobileSidebarOpen: boolean;
  
  // Active church context (for multi-church users)
  activeChurchId: number | null;
  
  // Feature flags / user preferences
  preferences: {
    compactMode: boolean;
    showWelcomeBanner: boolean;
    defaultCalendarView: 'month' | 'week' | 'day';
  };
  
  // Actions
  toggleSidebar: () => void;
  setMobileSidebarOpen: (open: boolean) => void;
  setActiveChurch: (churchId: number | null) => void;
  updatePreference: <K extends keyof AppState['preferences']>(
    key: K,
    value: AppState['preferences'][K]
  ) => void;
  resetPreferences: () => void;
}

const defaultPreferences: AppState['preferences'] = {
  compactMode: false,
  showWelcomeBanner: true,
  defaultCalendarView: 'month',
};

export const useAppStore = create<AppState>()(
  persist(
    (set) => ({
      // Initial state
      sidebarCollapsed: false,
      mobileSidebarOpen: false,
      activeChurchId: null,
      preferences: defaultPreferences,
      
      // Actions
      toggleSidebar: () => set((state) => ({
        sidebarCollapsed: !state.sidebarCollapsed,
      })),
      
      setMobileSidebarOpen: (open) => set({mobileSidebarOpen: open}),
      
      setActiveChurch: (churchId) => set({activeChurchId: churchId}),
      
      updatePreference: (key, value) => set((state) => ({
        preferences: {...state.preferences, [key]: value},
      })),
      
      resetPreferences: () => set({preferences: defaultPreferences}),
    }),
    {
      name: 'church-app-storage',
      partialize: (state) => ({
        sidebarCollapsed: state.sidebarCollapsed,
        activeChurchId: state.activeChurchId,
        preferences: state.preferences,
      }),
    }
  )
);
