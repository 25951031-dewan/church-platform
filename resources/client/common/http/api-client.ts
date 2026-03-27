import axios from 'axios';

export const apiClient = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
});

// Auto-refresh CSRF on 419
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 419) {
      await axios.get('/sanctum/csrf-cookie');
      return apiClient.request(error.config);
    }
    return Promise.reject(error);
  }
);

// Attach bearer token if stored
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
