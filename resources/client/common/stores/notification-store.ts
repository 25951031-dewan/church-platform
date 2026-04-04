import {create} from 'zustand';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  type: ToastType;
  message: string;
  title?: string;
  duration?: number;
  action?: {
    label: string;
    onClick: () => void;
  };
}

interface NotificationState {
  toasts: Toast[];
  
  // Real-time notification badge counts
  unreadCount: number;
  chatUnreadCount: number;
  
  // Actions
  addToast: (toast: Omit<Toast, 'id'>) => string;
  removeToast: (id: string) => void;
  clearToasts: () => void;
  
  // Convenience methods
  success: (message: string, title?: string) => string;
  error: (message: string, title?: string) => string;
  warning: (message: string, title?: string) => string;
  info: (message: string, title?: string) => string;
  
  // Badge counts
  setUnreadCount: (count: number) => void;
  setChatUnreadCount: (count: number) => void;
  incrementUnread: () => void;
  incrementChatUnread: () => void;
}

const generateId = () => `toast-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;

export const useNotificationStore = create<NotificationState>((set, get) => ({
  toasts: [],
  unreadCount: 0,
  chatUnreadCount: 0,
  
  addToast: (toast) => {
    const id = generateId();
    const newToast: Toast = {
      id,
      duration: 5000, // Default 5 seconds
      ...toast,
    };
    
    set((state) => ({
      toasts: [...state.toasts, newToast],
    }));
    
    // Auto-remove after duration
    if (newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        get().removeToast(id);
      }, newToast.duration);
    }
    
    return id;
  },
  
  removeToast: (id) => set((state) => ({
    toasts: state.toasts.filter((t) => t.id !== id),
  })),
  
  clearToasts: () => set({toasts: []}),
  
  // Convenience methods
  success: (message, title) => get().addToast({type: 'success', message, title}),
  error: (message, title) => get().addToast({type: 'error', message, title, duration: 8000}),
  warning: (message, title) => get().addToast({type: 'warning', message, title}),
  info: (message, title) => get().addToast({type: 'info', message, title}),
  
  // Badge counts
  setUnreadCount: (count) => set({unreadCount: count}),
  setChatUnreadCount: (count) => set({chatUnreadCount: count}),
  incrementUnread: () => set((state) => ({unreadCount: state.unreadCount + 1})),
  incrementChatUnread: () => set((state) => ({chatUnreadCount: state.chatUnreadCount + 1})),
}));
