import { create } from 'zustand';
import { ReactNode } from 'react';

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

export function BootstrapDataProvider({ children }: { children: ReactNode }) {
  return <>{children}</>;
}
