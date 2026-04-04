/// <reference types="vitest" />
import {defineConfig} from 'vitest/config';
import react from '@vitejs/plugin-react-swc';
import {resolve} from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./resources/client/test/setup.ts'],
    include: ['resources/client/**/*.{test,spec}.{ts,tsx}'],
    exclude: ['node_modules', 'vendor', 'storage'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      include: ['resources/client/**/*.{ts,tsx}'],
      exclude: [
        'resources/client/**/*.test.{ts,tsx}',
        'resources/client/**/*.spec.{ts,tsx}',
        'resources/client/test/**',
        'resources/client/**/*.d.ts',
      ],
    },
  },
  resolve: {
    alias: {
      '@app': resolve(__dirname, 'resources/client'),
      '@common': resolve(__dirname, 'common/foundation/resources/client'),
      '@ui': resolve(__dirname, 'resources/client/common/ui'),
    },
  },
});
