import {describe, it, expect, beforeEach} from 'vitest';
import {useNotificationStore} from '../common/stores/notification-store';

describe('useNotificationStore', () => {
  beforeEach(() => {
    // Reset store between tests
    useNotificationStore.setState({
      toasts: [],
      unreadCount: 0,
      chatUnreadCount: 0,
    });
  });

  it('should add a toast', () => {
    const {addToast} = useNotificationStore.getState();
    
    const id = addToast({
      type: 'success',
      message: 'Test message',
    });
    
    const {toasts} = useNotificationStore.getState();
    expect(toasts).toHaveLength(1);
    expect(toasts[0].id).toBe(id);
    expect(toasts[0].message).toBe('Test message');
    expect(toasts[0].type).toBe('success');
  });

  it('should remove a toast', () => {
    const {addToast, removeToast} = useNotificationStore.getState();
    
    const id = addToast({type: 'info', message: 'Test'});
    expect(useNotificationStore.getState().toasts).toHaveLength(1);
    
    removeToast(id);
    expect(useNotificationStore.getState().toasts).toHaveLength(0);
  });

  it('should have convenience methods', () => {
    const store = useNotificationStore.getState();
    
    store.success('Success!');
    store.error('Error!');
    store.warning('Warning!');
    store.info('Info!');
    
    const {toasts} = useNotificationStore.getState();
    expect(toasts).toHaveLength(4);
    expect(toasts.map(t => t.type)).toEqual(['success', 'error', 'warning', 'info']);
  });

  it('should track unread counts', () => {
    const store = useNotificationStore.getState();
    
    store.setUnreadCount(5);
    expect(useNotificationStore.getState().unreadCount).toBe(5);
    
    store.incrementUnread();
    expect(useNotificationStore.getState().unreadCount).toBe(6);
    
    store.setChatUnreadCount(3);
    store.incrementChatUnread();
    expect(useNotificationStore.getState().chatUnreadCount).toBe(4);
  });
});
