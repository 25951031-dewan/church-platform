import {describe, it, expect, beforeEach} from 'vitest';
import {useAppStore} from '../common/stores/app-store';

describe('useAppStore', () => {
  beforeEach(() => {
    // Reset store between tests
    useAppStore.setState({
      sidebarCollapsed: false,
      mobileSidebarOpen: false,
      activeChurchId: null,
      preferences: {
        compactMode: false,
        showWelcomeBanner: true,
        defaultCalendarView: 'month',
      },
    });
  });

  it('should toggle sidebar', () => {
    const {toggleSidebar} = useAppStore.getState();
    
    expect(useAppStore.getState().sidebarCollapsed).toBe(false);
    
    toggleSidebar();
    expect(useAppStore.getState().sidebarCollapsed).toBe(true);
    
    toggleSidebar();
    expect(useAppStore.getState().sidebarCollapsed).toBe(false);
  });

  it('should set mobile sidebar open state', () => {
    const {setMobileSidebarOpen} = useAppStore.getState();
    
    setMobileSidebarOpen(true);
    expect(useAppStore.getState().mobileSidebarOpen).toBe(true);
    
    setMobileSidebarOpen(false);
    expect(useAppStore.getState().mobileSidebarOpen).toBe(false);
  });

  it('should set active church', () => {
    const {setActiveChurch} = useAppStore.getState();
    
    setActiveChurch(123);
    expect(useAppStore.getState().activeChurchId).toBe(123);
    
    setActiveChurch(null);
    expect(useAppStore.getState().activeChurchId).toBeNull();
  });

  it('should update preferences', () => {
    const {updatePreference} = useAppStore.getState();
    
    updatePreference('compactMode', true);
    expect(useAppStore.getState().preferences.compactMode).toBe(true);
    
    updatePreference('defaultCalendarView', 'week');
    expect(useAppStore.getState().preferences.defaultCalendarView).toBe('week');
  });

  it('should reset preferences', () => {
    const {updatePreference, resetPreferences} = useAppStore.getState();
    
    updatePreference('compactMode', true);
    updatePreference('showWelcomeBanner', false);
    
    resetPreferences();
    
    const {preferences} = useAppStore.getState();
    expect(preferences.compactMode).toBe(false);
    expect(preferences.showWelcomeBanner).toBe(true);
  });
});
