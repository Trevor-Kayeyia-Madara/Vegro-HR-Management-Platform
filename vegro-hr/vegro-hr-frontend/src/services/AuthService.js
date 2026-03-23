import apiClient from '../api/apiClient';

const TOKEN_KEY = 'vegro_hr_token';

const initCsrf = () => apiClient.get('/sanctum/csrf-cookie');

const setToken = (token) => {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  }
};

const getToken = () => localStorage.getItem(TOKEN_KEY);

const clearToken = () => localStorage.removeItem(TOKEN_KEY);

const login = async (credentials) => {
  const response = await apiClient.post('/api/auth/login', credentials);
  const token = response?.data?.data?.token || response?.data?.token;
  setToken(token);
  return response;
};

const forgotPassword = (payload) => apiClient.post('/api/auth/forgot-password', payload);

const resetPassword = (payload) => apiClient.post('/api/auth/reset-password', payload);

const logout = async () => {
  try {
    await apiClient.post('/api/auth/logout');
  } finally {
    clearToken();
  }
};

const getCurrentUser = () => apiClient.get('/api/auth/me');
const updateCurrentUser = (payload) => apiClient.patch('/api/auth/me', payload);

const authCheck = () => apiClient.get('/api/auth/check');

const authService = {
  initCsrf,
  login,
  forgotPassword,
  resetPassword,
  logout,
  getCurrentUser,
  updateCurrentUser,
  authCheck,
  setToken,
  getToken,
  clearToken,
};

export default authService;
