<!-- eslint-disable no-unused-vars -->
<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import useAuth from '../hooks/useAuth';
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
const { hasRole, hasPermission, logout: authLogout } = useAuth();

const adminNavItems = [
  { label: 'Dashboard', to: '/dashboard/home', icon: LayoutDashboard, roles: ['admin'], permissions: 'dashboard.view' },
  { label: 'Users', to: '/dashboard/users', icon: UserRound, roles: ['admin'], permissions: 'users.manage' },
  { label: 'RBAC Roles', to: '/dashboard/roles', icon: ShieldCheck, roles: ['admin'], permissions: 'roles.manage' },
  { label: 'Profile', to: '/dashboard/profile', icon: FileText, roles: ['admin', 'hr', 'finance', 'manager', 'employee'], permissions: 'profile.view' },
  { label: 'Settings', to: '/dashboard/settings', icon: Settings, roles: ['admin'], permissions: 'settings.manage' },
];

const standardNavItems = [
  { label: 'Dashboard', to: '/dashboard/hr', icon: LayoutDashboard, roles: ['hr'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/finance', icon: LayoutDashboard, roles: ['finance'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/employee', icon: LayoutDashboard, roles: ['employee'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/manager', icon: LayoutDashboard, roles: ['manager'], permissions: 'dashboard.view' },
  { label: 'Dashboard', to: '/dashboard/director', icon: LayoutDashboard, roles: ['director', 'md'], permissions: 'dashboard.view' },
  { label: 'Employees', to: '/dashboard/employees', icon: Users, roles: ['admin', 'hr'], permissions: 'employees.view' },
  { label: 'Departments', to: '/dashboard/departments', icon: Building2, roles: ['admin', 'hr'], permissions: 'departments.view' },
  { label: 'Payroll', to: '/dashboard/payroll', icon: Wallet, roles: ['admin', 'hr', 'finance'], permissions: 'payroll.view' },
  { label: 'Attendance', to: '/dashboard/attendance', icon: ClipboardCheck, roles: ['admin', 'hr'], permissions: 'attendance.view' },
  { label: 'Leaves', to: '/dashboard/leaves', icon: CalendarDays, roles: ['admin', 'hr', 'manager', 'employee'], permissions: 'leaves.view' },
  { label: 'Payslips', to: '/dashboard/payslips', icon: FileText, roles: ['admin', 'hr', 'finance'], permissions: 'payslips.view' },
  { label: 'Tax Profiles', to: '/dashboard/tax-profiles', icon: Landmark, roles: ['admin', 'finance'], permissions: 'taxprofiles.view' },
  { label: 'Profile', to: '/dashboard/profile', icon: FileText, roles: ['admin', 'hr', 'finance', 'manager', 'employee'], permissions: 'profile.view' },
];

const isActive = (path) => route.path === path;

const isAdmin = computed(() => hasRole(['admin']));

const visibleNavItems = computed(() => {
  const items = isAdmin.value ? adminNavItems : standardNavItems;
  return items.filter((item) => {
    const roleAllowed = !item.roles || hasRole(item.roles);
    const permissionAllowed = !item.permissions || hasPermission(item.permissions);
    return roleAllowed && permissionAllowed;
  });
});

const logout = async () => {
  await authLogout();
  router.push('/login');
  emit('close');
};

const activeLabel = computed(() => visibleNavItems.value.find((item) => isActive(item.to))?.label || 'Menu');

const handleNavigate = () => {
  if (props.isMobile) emit('close');
};
</script>

<template>
  <aside
    class="flex h-full max-h-screen flex-col gap-6 overflow-y-auto border-r border-white/10 bg-slate-950 text-white"
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
        :key="item.label"
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
      @click="logout"
    >
      <LogOut class="h-5 w-5" />
      <span v-if="!compact" class="font-medium">Logout</span>
    </button>
  </aside>
</template>
