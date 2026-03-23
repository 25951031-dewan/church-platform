import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            workbox: {
                runtimeCaching: [
                    // PDFs — CacheFirst, 30 days
                    {
                        urlPattern: /\.pdf$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'pdf-cache',
                            expiration: { maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                    // Hymn audio — CacheFirst, 7 days
                    {
                        urlPattern: /\.(mp3|ogg|wav|m4a)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'hymn-audio-cache',
                            expiration: { maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 7 },
                        },
                    },
                    // Bible / verse API — StaleWhileRevalidate (instant + fresh)
                    {
                        urlPattern: /\/api\/v1\/(verse|bible)\//i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'verse-bible-cache',
                            expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 7 },
                        },
                    },
                    // Sermons / library content — StaleWhileRevalidate, 3 days
                    {
                        urlPattern: /\/api\/v1\/(sermons|library)\//i,
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'content-cache',
                            expiration: { maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 3 },
                        },
                    },
                ],
            },
            manifest: {
                name: 'Church Platform',
                short_name: 'Church',
                theme_color: '#2563eb',
                icons: [
                    { src: '/icons/pwa-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/pwa-512.png', sizes: '512x512', type: 'image/png' },
                ],
            },
        }),
    ],
});
