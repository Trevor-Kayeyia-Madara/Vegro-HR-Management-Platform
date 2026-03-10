import { ref } from 'vue';
import authService from '../services/authService';

const user = ref(null);
const isLoading = ref(false);
const error = ref('');

const fetchUser = async () => {
  error.value = '';
  isLoading.value = true;

  try {
    const response = await authService.getCurrentUser();
    user.value = response?.data?.user ?? response?.data ?? null;
    return user.value;
  } catch (err) {
    user.value = null;
    if (err?.response?.status === 401) {
      authService.clearToken();
    }
    error.value = err?.response?.data?.message || 'Unable to fetch user.';
    return null;
  } finally {
    isLoading.value = false;
  }
};

const checkAuth = () => fetchUser();

const logout = async () => {
  error.value = '';
  isLoading.value = true;

  try {
    await authService.logout();
  } finally {
    user.value = null;
    isLoading.value = false;
  }
};

export default function useAuth() {
  return {
    user,
    isLoading,
    error,
    checkAuth,
    fetchUser,
    logout,
  };
}
