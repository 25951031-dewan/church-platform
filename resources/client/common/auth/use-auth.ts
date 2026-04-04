import { useMutation } from '@tanstack/react-query';
import { apiClient } from '../http/api-client';
import { useBootstrapStore } from '../core/bootstrap-data';

export function useAuth() {
  const { user, setUser } = useBootstrapStore();

  const loginMutation = useMutation({
    mutationFn: async (data: { email: string; password: string }) => {
      const res = await apiClient.post('login', data);
      localStorage.setItem('auth_token', res.data.token);
      setUser(res.data.user);
      return res.data;
    },
  });

  const registerMutation = useMutation({
    mutationFn: async (data: { name: string; email: string; password: string; password_confirmation: string }) => {
      const res = await apiClient.post('register', data);
      localStorage.setItem('auth_token', res.data.token);
      setUser(res.data.user);
      return res.data;
    },
  });

  const logout = async () => {
    await apiClient.post('logout').catch(() => {});
    localStorage.removeItem('auth_token');
    setUser(null);
    window.location.href = '/';
  };

  return {
    user,
    isAuthenticated: !!user,
    login: loginMutation,
    register: registerMutation,
    logout,
  };
}
