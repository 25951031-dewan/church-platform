import React from 'react';
import { useIsOffline } from '../../hooks/useOfflineStorage';

/**
 * Shows a subtle "Available Offline" banner when the device has no network.
 * Mount this at the top level of your app layout.
 */
export default function OfflineIndicator() {
    const offline = useIsOffline();

    if (!offline) return null;

    return (
        <div
            role="status"
            aria-live="polite"
            style={{
                position: 'fixed',
                bottom: '1rem',
                left: '50%',
                transform: 'translateX(-50%)',
                background: '#1e293b',
                color: '#f8fafc',
                padding: '0.5rem 1.25rem',
                borderRadius: '9999px',
                fontSize: '0.875rem',
                fontWeight: 500,
                zIndex: 9999,
                display: 'flex',
                alignItems: 'center',
                gap: '0.5rem',
                boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
            }}
        >
            <span aria-hidden>📶</span>
            Available Offline
        </div>
    );
}
