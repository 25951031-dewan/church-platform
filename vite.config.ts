import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react-swc';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/client/main.tsx'],
      refresh: true,
    }),
    react(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@app': resolve(__dirname, 'resources/client'),
      '@common': resolve(__dirname, 'common/foundation/resources/client'),
      '@ui': resolve(__dirname, 'resources/client/common/ui'),
    },
  },
});
