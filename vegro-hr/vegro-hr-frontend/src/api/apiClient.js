import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

const apiClient = axios.create({
  baseURL,
  withCredentials: true,
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('vegro_hr_token');
  if (token) {
    config.headers = {
      ...config.headers,
      Authorization: `Bearer ${token}`,
    };
  }
  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status;

    if (status === 401) {
      localStorage.removeItem('vegro_hr_token');
      if (window.location.pathname !== '/login') {
        window.location.href = '/login';
      }
    }

    if (status === 419) {
      console.error('Session expired. Please refresh and try again.');
    }

    if (status === 500) {
      console.error('Server error. Please try again later.');
    }

    return Promise.reject(error);
  },
);

export default apiClient;
