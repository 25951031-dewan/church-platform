import './app.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { BrowserRouter } from 'react-router';
import { AppRouter } from './app-router';
import { queryClient } from './common/http/query-client';
import { BootstrapDataProvider } from './common/core/bootstrap-data';
import { AudioPlayerBar } from './common/audio-player/AudioPlayerBar';

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <BootstrapDataProvider>
        <BrowserRouter>
          <AppRouter />
          <AudioPlayerBar />
        </BrowserRouter>
      </BootstrapDataProvider>
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  </StrictMode>
);
