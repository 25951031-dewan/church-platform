import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30 * 1000,
      retry: (failureCount, error: any) => {
        const status = error?.response?.status;
        if ([401, 403, 404].includes(status)) return false;
        return failureCount < 2;
      },
    },
  },
});
