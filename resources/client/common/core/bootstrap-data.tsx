import { create } from 'zustand';
import { ReactNode, useEffect, useState } from 'react';
import axios from 'axios';

interface BootstrapUser {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  permissions: Record<string, boolean>;
  role_level: number;
  roles: string[];
  theme: string;
  language: string;
}

interface BootstrapData {
  user: BootstrapUser | null;
  settings: Record<string, string>;
  plugins: string[];
}

interface BootstrapStore extends BootstrapData {
  setUser: (user: BootstrapUser | null) => void;
}

declare global {
  interface Window {
    __BOOTSTRAP_DATA__: BootstrapData;
  }
}

export const useBootstrapStore = create<BootstrapStore>((set) => ({
  ...(window.__BOOTSTRAP_DATA__ ?? { user: null, settings: {}, plugins: [] }),
  setUser: (user) => set({ user }),
}));

export function useBootstrapData() {
  return useBootstrapStore();
}

/**
 * On mount: if a token exists in localStorage but the server-injected
 * bootstrap data has no user (token-based auth, stateless), fetch /api/v1/me
 * to rehydrate the user into the store before rendering protected routes.
 */
export function BootstrapDataProvider({ children }: { children: ReactNode }) {
  const { user, setUser } = useBootstrapStore();
  const [ready, setReady] = useState(!!user);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (!user && token) {
      axios.get('/api/v1/me', {
        headers: { Authorization: `Bearer ${token}` },
        withCredentials: true,
      }).then(res => {
        if (res.data?.user) setUser(res.data.user);
      }).catch(() => {
        localStorage.removeItem('auth_token');
      }).finally(() => {
        setReady(true);
      });
    } else {
      setReady(true);
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  if (!ready) return null;

  return <>{children}</>;
}
