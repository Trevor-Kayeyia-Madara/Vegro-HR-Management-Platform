import { computed, ref } from 'vue';
import authService from '../services/AuthService';

const user = ref(null);
const isLoading = ref(false);
const error = ref('');

const normalizeRole = (value) => {
  const normalized = String(value || '')
    .toLowerCase()
    .replace(/[\s-_]/g, '');
  const aliases = {
    hrmanager: 'hr',
    humanresourcesmanager: 'hr',
    administrator: 'admin',
    companyadmin: 'admin',
    companyadministrator: 'admin',
    financemanager: 'financemanager',
  };
  return aliases[normalized] || normalized;
};

const roleTitle = computed(() =>
  normalizeRole(user.value?.role?.title || user.value?.role?.name || user.value?.role),
);

const isSuperAdmin = computed(() => roleTitle.value === 'superadmin');
const isAdmin = computed(() => roleTitle.value === 'admin');

const permissions = computed(() => user.value?.role?.permissions || []);

const hasRole = (roles = []) => {
  const list = Array.isArray(roles) ? roles : [roles];
  const normalized = list.map((role) => normalizeRole(role));
  if (isSuperAdmin.value) return normalized.includes('superadmin');
  if (isAdmin.value) return true;
  if (roleTitle.value === 'financemanager') {
    return normalized.includes('financemanager') || normalized.includes('finance') || normalized.includes('manager');
  }
  return normalized.includes(roleTitle.value);
};

const hasPermission = (permissionKey) => {
  if (isSuperAdmin.value) return true;
  if (isAdmin.value) return true;
  if (!permissionKey) return true;
  const keys = Array.isArray(permissionKey) ? permissionKey : [permissionKey];
  const normalized = keys.map((key) => normalizeRole(key));
  return permissions.value.some((permission) => normalized.includes(normalizeRole(permission?.key)));
};

const fetchUser = async () => {
  error.value = '';
  isLoading.value = true;

  try {
    const response = await authService.getCurrentUser();
    const payload = response?.data?.data ?? response?.data ?? null;
    user.value = payload?.user ?? payload ?? null;
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
    roleTitle,
    isSuperAdmin,
    isAdmin,
    permissions,
    hasRole,
    hasPermission,
    checkAuth,
    fetchUser,
    logout,
  };
}
