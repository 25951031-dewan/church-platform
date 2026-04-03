import React from 'react';
import { createRoot } from 'react-dom/client';
import PwaApp from './pwa/PwaApp';

const container = document.getElementById('pwa-root');
if (container) {
    createRoot(container).render(<PwaApp />);
}
