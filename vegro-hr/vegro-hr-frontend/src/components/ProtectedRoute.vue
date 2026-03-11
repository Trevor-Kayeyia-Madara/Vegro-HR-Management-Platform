<script setup>
import { computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import useAuth from '../hooks/useAuth';

defineOptions({ name: 'ProtectedRoute' });

const router = useRouter();
const route = useRoute();
const { user, isLoading, checkAuth, hasRole, hasPermission, isAdmin } = useAuth();

const requiredRoles = computed(() => {
  const matched = route.matched;
  for (let i = matched.length - 1; i >= 0; i -= 1) {
    const roles = matched[i].meta?.roles;
    if (roles) return roles;
  }
  return null;
});

const requiredPermissions = computed(() => {
  const matched = route.matched;
  for (let i = matched.length - 1; i >= 0; i -= 1) {
    const permissions = matched[i].meta?.permissions;
    if (permissions) return permissions;
  }
  return null;
});

const isAllowed = computed(() => {
  if (!user.value) return false;
  const roleAllowed = !requiredRoles.value || hasRole(requiredRoles.value);
  const permissionAllowed = !requiredPermissions.value || hasPermission(requiredPermissions.value);
  return roleAllowed && permissionAllowed;
});

const getFallbackRoute = () => {
  if (isAdmin.value) return '/dashboard/home';
  if (hasRole(['hr'])) return '/dashboard/hr';
  if (hasRole(['finance'])) return '/dashboard/finance';
  if (hasRole(['manager'])) return '/dashboard/manager';
  if (hasRole(['director', 'md'])) return '/dashboard/director';
  if (hasRole(['employee'])) return '/dashboard/employee';
  return '/dashboard/profile';
};

const guardRoute = () => {
  if (!user.value) {
    router.replace('/login');
    return;
  }

  if (!isAllowed.value) {
    router.replace(getFallbackRoute());
  }
};

onMounted(async () => {
  if (!user.value) {
    await checkAuth();
  }

  guardRoute();
});

watch(
  () => [user.value, route.fullPath],
  () => {
    guardRoute();
  },
);
</script>

<template>
  <div v-if="isLoading" class="p-6 text-sm text-slate-400">
    Checking session...
  </div>
  <router-view v-else-if="isAllowed" />
</template>
