import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo: Echo<'pusher'>;
  }
}

// Make Pusher available globally (required by Echo)
window.Pusher = Pusher;

/**
 * Create and configure Laravel Echo instance for real-time communication.
 * Uses Pusher driver by default (shared hosting compatible).
 * Can be switched to Reverb by changing BROADCAST_DRIVER in .env.
 */
function createEchoInstance(): Echo<'pusher'> {
  const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;
  const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';

  if (!pusherKey) {
    console.warn(
      '[Echo] VITE_PUSHER_APP_KEY not set. Real-time features will be disabled.'
    );
    // Return a mock Echo instance that does nothing
    return {
      private: () => ({
        listen: () => ({ listen: () => ({}) }),
        stopListening: () => ({}),
      }),
      join: () => ({
        here: () => ({ joining: () => ({ leaving: () => ({}) }) }),
      }),
      leave: () => {},
      channel: () => ({
        listen: () => ({}),
      }),
    } as unknown as Echo<'pusher'>;
  }

  return new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        Accept: 'application/json',
      },
    },
  });
}

// Create the Echo instance
export const echo = createEchoInstance();

// Make Echo available globally for debugging
window.Echo = echo;

export default echo;
