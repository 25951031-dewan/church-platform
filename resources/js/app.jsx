import React from 'react';
import { createRoot } from 'react-dom/client';
import AdminApp from './components/AdminApp';

const adminEl = document.getElementById('admin-app');
if (adminEl) {
    const section = adminEl.dataset.section;
    const root = createRoot(adminEl);
    root.render(<AdminApp section={section} />);
}
