import OneSignal from 'react-onesignal';
import {apiClient} from '@app/common/http/api-client';

let initialized = false;

export async function initOneSignal() {
  if (initialized) return;

  const appId = (window as any).__BOOTSTRAP_DATA__?.settings?.['notifications.onesignal_app_id'];
  if (!appId) return;

  await OneSignal.init({
    appId,
    allowLocalhostAsSecureOrigin: true,
    notifyButton: {
      enable: false,
      prenotify: false,
      showCredit: false,
      text: {
        'tip.state.unsubscribed': '',
        'tip.state.subscribed': '',
        'tip.state.blocked': '',
        'message.prenotify': '',
        'message.action.subscribing': '',
        'message.action.subscribed': '',
        'message.action.resubscribed': '',
        'message.action.unsubscribed': '',
        'dialog.main.title': '',
        'dialog.main.button.subscribe': '',
        'dialog.main.button.unsubscribe': '',
        'dialog.blocked.title': '',
        'dialog.blocked.message': '',
      },
    },
  });

  const permission = await OneSignal.Notifications.permission;
  if (permission && localStorage.getItem('auth_token')) {
    const id = await OneSignal.User.PushSubscription.id;
    if (id) {
      await apiClient.post('notifications/push/register', {
        player_id: id,
        device_type: 'web',
        device_name: navigator.userAgent,
      });
    }
  }

  initialized = true;
}
