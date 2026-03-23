<script setup>
import { computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import useAuth from '../hooks/useAuth';

defineOptions({ name: 'ProtectedRoute' });

const router = useRouter();
const route = useRoute();
const { user, isLoading, checkAuth, hasRole, hasPermission, isAdmin, isSuperAdmin, roleTitle } = useAuth();

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
  if (isSuperAdmin.value) return '/dashboard/super';
  if (isAdmin.value) return '/dashboard/home';
  if (hasRole(['financemanager'])) return '/dashboard/finance-manager';
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

  if (roleTitle.value === 'financemanager' && route.path === '/dashboard/finance') {
    router.replace('/dashboard/finance-manager');
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
  <div
    v-if="isLoading"
    class="flex min-h-screen items-center justify-center bg-slate-950 px-6 text-white"
  >
    <div class="relative w-full max-w-sm overflow-hidden rounded-3xl border border-white/10 bg-white/5 p-8 text-center shadow-[0_25px_80px_rgba(15,23,42,0.55)]">
      <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.2),_transparent_55%)]"></div>
      <div class="relative mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-300/30 bg-emerald-300/10 text-lg font-semibold text-emerald-200">
        V
      </div>
      <p class="relative mt-4 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-200/80">
        Vegro HR
      </p>
      <h2 class="relative mt-2 text-lg font-semibold">Preparing your workspace</h2>
      <p class="relative mt-1 text-sm text-slate-300/80">Securing session and loading dashboard...</p>

      <div class="relative mt-6 flex items-center justify-center gap-2">
        <span class="loader-dot h-2.5 w-2.5 rounded-full bg-emerald-300/90"></span>
        <span class="loader-dot h-2.5 w-2.5 rounded-full bg-emerald-300/90 [animation-delay:0.16s]"></span>
        <span class="loader-dot h-2.5 w-2.5 rounded-full bg-emerald-300/90 [animation-delay:0.32s]"></span>
      </div>
    </div>
  </div>
  <router-view v-else-if="isAllowed" />
</template>

<style scoped>
.loader-dot {
  animation: vegro-bounce 0.9s ease-in-out infinite;
}

@keyframes vegro-bounce {
  0%,
  80%,
  100% {
    transform: translateY(0);
    opacity: 0.45;
  }
  40% {
    transform: translateY(-5px);
    opacity: 1;
  }
}
</style>
