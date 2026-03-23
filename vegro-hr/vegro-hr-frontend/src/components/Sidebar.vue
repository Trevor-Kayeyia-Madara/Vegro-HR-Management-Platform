<!-- eslint-disable no-unused-vars -->
<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import useAuth from '../hooks/useAuth';
import apiClient from '../api/apiClient';
import {
  LayoutDashboard,
  Grid2x2,
  UserRound,
  Users,
  Building2,
  Wallet,
  Landmark,
  ClipboardCheck,
  CalendarDays,
  FileText,
  Briefcase,
  MessageSquare,
  FileSignature,
  ShieldAlert,
  ClipboardList,
  SlidersHorizontal,
  LogOut,
  Settings,
  ShieldCheck,
} from 'lucide-vue-next';

defineOptions({ name: 'SidebarNav' });

const props = defineProps({
  isMobile: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const route = useRoute();
const router = useRouter();
const { hasRole, hasPermission, logout: authLogout, isSuperAdmin, isAdmin, roleTitle, user } = useAuth();

const superAdminNavItems = [
  { label: 'superadmin', to: '/dashboard/super', icon: ShieldCheck, roles: ['superadmin'], permissions: 'dashboard.view' },
  { label: 'Companies', to: '/dashboard/super/companies', icon: Building2, roles: ['superadmin'], permissions: 'dashboard.view' },
  { label: 'Roles', to: '/dashboard/super/roles', icon: ShieldCheck, roles: ['superadmin'], permissions: 'dashboard.view' },
  { label: 'System Users', to: '/dashboard/super/users', icon: Users, roles: ['superadmin'], permissions: 'dashboard.view' },
  { label: 'Billing', to: '/dashboard/super/billing', icon: Wallet, roles: ['superadmin'], permissions: 'dashboard.view' },
];

const adminNavItems = [
  { label: 'Dashboard', to: '/dashboard/home', icon: LayoutDashboard, roles: ['admin'], permissions: 'dashboard.view' },
  { label: 'Users', to: '/dashboard/users', icon: UserRound, roles: ['admin'], permissions: 'users.manage' },
  { label: 'RBAC Roles', to: '/dashboard/roles', icon: ShieldCheck, roles: ['admin'], permissions: 'roles.manage' },
  { label: 'Recruitment', to: '/dashboard/recruitment', icon: Briefcase, roles: ['admin', 'hr', 'manager', 'director', 'md'], permissions: 'recruitment.view' },
  { label: 'Feedback', to: '/dashboard/feedback', icon: MessageSquare, roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'feedback.submit' },
  { label: 'Drive', to: '/dashboard/onboarding', icon: FileSignature, roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'onboarding.view' },
  { label: 'Reports', to: '/dashboard/reports', icon: FileText, roles: ['admin'], permissions: null },
  { label: 'Compliance', to: '/dashboard/compliance', icon: ShieldAlert, roles: ['admin', 'hr', 'finance', 'manager', 'director', 'md'], permissions: 'compliance.view' },
  { label: 'Audits', to: '/dashboard/audits', icon: ClipboardList, roles: ['admin', 'hr', 'finance', 'director', 'md'], permissions: 'audits.view' },
  { label: 'Dashboards', to: '/dashboard/dashboards', icon: Grid2x2, roles: ['admin'], permissions: null },
  { label: 'Profile', to: '/dashboard/profile', icon: FileText, roles: ['admin', 'hr', 'finance', 'manager', 'employee'], permissions: 'profile.view' },
  { label: 'Settings', to: '/dashboard/settings', icon: Settings, roles: ['admin'], permissions: 'settings.manage' },
];

const standardNavItems = [
  { label: 'Dashboard', to: '/dashboard/hr', icon: LayoutDashboard, roles: ['hr'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/finance', icon: LayoutDashboard, roles: ['finance'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/finance-manager', icon: LayoutDashboard, roles: ['financemanager'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/employee', icon: LayoutDashboard, roles: ['employee'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/manager', icon: LayoutDashboard, roles: ['manager'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/director', icon: LayoutDashboard, roles: ['director', 'md'], permissions: 'dashboard.view' },
  { label: 'Department Team', to: '/dashboard/my-team', icon: Users, roles: ['manager'], permissions: 'employees.view' },
  { label: 'Employees', to: '/dashboard/employees', icon: Users, roles: ['admin', 'hr'], permissions: 'employees.view' },
  { label: 'Org Chart', to: '/dashboard/org-chart', icon: Users, roles: ['hr'], permissions: null },
  { label: 'Recruitment', to: '/dashboard/recruitment', icon: Briefcase, roles: ['admin', 'hr', 'manager', 'director', 'md'], permissions: 'recruitment.view' },
  { label: 'Feedback', to: '/dashboard/feedback', icon: MessageSquare, roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'feedback.submit' },
  { label: 'Drive', to: '/dashboard/onboarding', icon: FileSignature, roles: ['admin', 'hr', 'finance', 'manager', 'employee', 'director', 'md'], permissions: 'onboarding.view' },
  { label: 'Projects', to: '/dashboard/projects', icon: Grid2x2, roles: ['admin', 'hr', 'manager', 'employee', 'director', 'md'], permissions: 'projects.view' },
  { label: 'Payroll', to: '/dashboard/payroll', icon: Wallet, roles: ['admin', 'hr', 'finance'], permissions: 'payroll.view' },
  { label: 'Reports', to: '/dashboard/reports', icon: FileText, roles: ['admin', 'hr', 'finance', 'director', 'md'], permissions: null },
  { label: 'Compliance', to: '/dashboard/compliance', icon: ShieldAlert, roles: ['admin', 'hr', 'finance', 'manager', 'director', 'md'], permissions: 'compliance.view' },
  { label: 'Audits', to: '/dashboard/audits', icon: ClipboardList, roles: ['admin', 'hr', 'finance', 'director', 'md'], permissions: 'audits.view' },
  { label: 'Dashboards', to: '/dashboard/dashboards', icon: Grid2x2, roles: ['admin', 'hr', 'finance', 'director', 'md'], permissions: null },
  { label: 'Attendance', to: '/dashboard/attendance', icon: ClipboardCheck, roles: ['admin', 'hr'], permissions: 'attendance.view' },
  { label: 'Leaves', to: '/dashboard/leaves', icon: CalendarDays, roles: ['admin', 'hr', 'manager', 'employee'], permissions: 'leaves.view' },
  { label: 'Payslips', to: '/dashboard/payslips', icon: FileText, roles: ['admin', 'hr', 'finance'], permissions: 'payslips.view' },
  { label: 'Tax Profiles', to: '/dashboard/tax-profiles', icon: Landmark, roles: ['admin', 'finance'], permissions: 'taxprofiles.view' },
  { label: 'Profile', to: '/dashboard/profile', icon: FileText, roles: ['admin', 'hr', 'finance', 'manager', 'employee'], permissions: 'profile.view' },
];

const isActive = (path) => route.path === path;

const isFinanceManager = computed(() => roleTitle.value === 'financemanager');
const canViewDashboards = computed(() => hasRole(['admin', 'hr', 'finance', 'financemanager', 'director', 'md']));
const dashboardShortcuts = ref([]);

const loadDashboardShortcuts = async () => {
  if (!canViewDashboards.value || isSuperAdmin.value) {
    dashboardShortcuts.value = [];
    return;
  }
  try {
    const response = await apiClient.get('/api/dashboards');
    const items = response?.data?.data || [];
    dashboardShortcuts.value = items.map((dashboard) => ({
      label: dashboard?.name || `Dashboard ${dashboard?.id}`,
      to: `/dashboard/dashboards/${dashboard.id}`,
      icon: Grid2x2,
      roles: ['admin', 'hr', 'finance', 'director', 'md'],
      permissions: null,
      dynamic: true,
    }));
  } catch {
    dashboardShortcuts.value = [];
  }
};

const availableNavItems = computed(() => {
  if (isSuperAdmin.value) {
    return superAdminNavItems;
  }
  const items = isAdmin.value ? adminNavItems : standardNavItems;
  const filtered = items.filter((item) => {
    const roleAllowed = !item.roles || hasRole(item.roles);
    const permissionAllowed = !item.permissions || hasPermission(item.permissions);
    return roleAllowed && permissionAllowed;
  });
  const roleFiltered = isFinanceManager.value
    ? filtered.filter((item) => item.label !== 'Dashboard' || item.to === '/dashboard/finance-manager')
    : filtered;
  const dashboardsIndex = roleFiltered.findIndex((item) => item.to === '/dashboard/dashboards');
  if (dashboardsIndex === -1 || !dashboardShortcuts.value.length) {
    return roleFiltered;
  }
  const dynamic = dashboardShortcuts.value.filter((item) => {
    const roleAllowed = !item.roles || hasRole(item.roles);
    const permissionAllowed = !item.permissions || hasPermission(item.permissions);
    return roleAllowed && permissionAllowed;
  });
  return [
    ...roleFiltered.slice(0, dashboardsIndex + 1),
    ...dynamic,
    ...roleFiltered.slice(dashboardsIndex + 1),
  ];
});

const sidebarStorageKey = computed(() => {
  const userId = user.value?.id ?? 'anon';
  const role = roleTitle.value || 'role';
  return `vegro_hr_sidebar_hidden_${userId}_${role}`;
});

const hiddenNavPaths = ref(new Set());

const loadHiddenNavPaths = () => {
  try {
    const raw = localStorage.getItem(sidebarStorageKey.value);
    const parsed = raw ? JSON.parse(raw) : [];
    hiddenNavPaths.value = new Set(Array.isArray(parsed) ? parsed : []);
  } catch {
    hiddenNavPaths.value = new Set();
  }
};

const persistHiddenNavPaths = () => {
  localStorage.setItem(sidebarStorageKey.value, JSON.stringify([...hiddenNavPaths.value]));
};

const isPinnedItem = (item) =>
  String(item?.label || '').toLowerCase() === 'dashboard' || item?.to === '/dashboard/org-chart' || item?.dynamic === true;

const sanitizeHiddenNavPaths = () => {
  let changed = false;
  const next = new Set(hiddenNavPaths.value);

  availableNavItems.value.forEach((item) => {
    if (isPinnedItem(item) && next.has(item.to)) {
      next.delete(item.to);
      changed = true;
    }
  });

  const visibleCount = availableNavItems.value.filter((item) => !next.has(item.to)).length;
  if (!visibleCount && availableNavItems.value.length) {
    next.delete(availableNavItems.value[0].to);
    changed = true;
  }

  if (changed) {
    hiddenNavPaths.value = next;
    persistHiddenNavPaths();
  }
};

watch(sidebarStorageKey, () => loadHiddenNavPaths(), { immediate: true });
watch(availableNavItems, () => sanitizeHiddenNavPaths(), { immediate: true });

const visibleNavItems = computed(() =>
  availableNavItems.value.filter((item) => !hiddenNavPaths.value.has(item.to)),
);

const logout = async () => {
  await authLogout();
  router.push('/login');
  emit('close');
};

const activeLabel = computed(() => visibleNavItems.value.find((item) => isActive(item.to))?.label || 'Menu');

const handleNavigate = () => {
  if (props.isMobile) emit('close');
};

const settingsOpen = ref(false);

const toggleNavVisibility = (item) => {
  if (!item?.to || isPinnedItem(item)) return;
  if (hiddenNavPaths.value.has(item.to)) {
    hiddenNavPaths.value.delete(item.to);
  } else {
    hiddenNavPaths.value.add(item.to);
  }
  hiddenNavPaths.value = new Set(hiddenNavPaths.value);
  persistHiddenNavPaths();
};

onMounted(() => {
  loadHiddenNavPaths();
  sanitizeHiddenNavPaths();
  loadDashboardShortcuts();
  window.addEventListener('vegro:dashboards:updated', loadDashboardShortcuts);
});

onBeforeUnmount(() => {
  window.removeEventListener('vegro:dashboards:updated', loadDashboardShortcuts);
});

watch(canViewDashboards, () => {
  loadDashboardShortcuts();
}, { immediate: true });
</script>

<template>
  <aside
    class="hide-scrollbar flex h-full max-h-screen flex-col gap-6 overflow-y-auto border-r border-white/10 bg-slate-950 text-white"
    :class="compact ? 'px-3 py-6' : 'px-6 py-8'"
  >
    <div class="flex flex-col gap-2" :class="compact ? 'items-center text-center' : ''">
      <span class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
        {{ compact ? 'VHR' : 'Vegro HR' }}
      </span>
      <h2 v-if="!compact" class="text-lg font-semibold">Workspace</h2>
      <p v-if="!compact" class="text-xs text-slate-400">Active: {{ activeLabel }}</p>
    </div>

    <nav class="flex flex-1 flex-col gap-2">
      <RouterLink
        v-for="item in visibleNavItems"
        :key="item.to"
        :to="item.to"
        class="group flex items-center gap-3 rounded-2xl border border-transparent px-3 py-3 text-sm text-slate-200 transition hover:border-white/10 hover:bg-white/5"
        :class="isActive(item.to) ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200' : ''"
        @click="handleNavigate"
      >
        <component :is="item.icon" class="h-5 w-5" />
        <span v-if="!compact" class="font-medium">{{ item.label }}</span>
      </RouterLink>
    </nav>

    <button
      class="flex items-center gap-3 rounded-2xl border border-white/10 px-3 py-3 text-sm text-slate-200 transition hover:bg-white/5"
      type="button"
      @click="settingsOpen = true"
    >
      <SlidersHorizontal class="h-5 w-5" />
      <span v-if="!compact" class="font-medium">Sidebar settings</span>
    </button>

    <button
      class="flex items-center gap-3 rounded-2xl border border-white/10 px-3 py-3 text-sm text-slate-200 transition hover:bg-white/5"
      type="button"
      @click="logout"
    >
      <LogOut class="h-5 w-5" />
      <span v-if="!compact" class="font-medium">Logout</span>
    </button>
  </aside>

  <div
    v-if="settingsOpen"
    class="vegro-modal-viewport"
    @click.self="settingsOpen = false"
  >
    <div class="vegro-modal max-w-xl p-4 sm:p-6 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)]">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Sidebar</p>
          <h2 class="mt-2 text-2xl font-semibold">Hide / unhide menu</h2>
          <p class="mt-1 text-sm text-slate-300/70">Stored locally for this account on this device.</p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="settingsOpen = false"
        >
          Close
        </button>
      </div>

      <div class="mt-6 max-h-[58dvh] space-y-3 overflow-y-auto pr-1">
        <div
          v-for="item in availableNavItems"
          :key="item.to"
          class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm"
        >
          <div class="flex items-center gap-3">
            <component :is="item.icon" class="h-5 w-5 text-slate-200" />
            <div>
              <p class="font-medium text-white">{{ item.label }}</p>
              <p class="text-xs text-slate-400">{{ item.to }}</p>
            </div>
          </div>

          <label class="flex items-center gap-2 text-xs text-slate-300/80">
            <input
              type="checkbox"
              class="h-4 w-4 accent-emerald-400"
              :disabled="isPinnedItem(item)"
              :checked="!hiddenNavPaths.has(item.to)"
              @change="toggleNavVisibility(item)"
            />
            <span>{{ isPinnedItem(item) ? 'Always' : 'Show' }}</span>
          </label>
        </div>
      </div>

      <div class="mt-6 flex justify-end">
        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="settingsOpen = false"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hide-scrollbar {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.hide-scrollbar::-webkit-scrollbar {
  width: 0;
  height: 0;
}
</style>
