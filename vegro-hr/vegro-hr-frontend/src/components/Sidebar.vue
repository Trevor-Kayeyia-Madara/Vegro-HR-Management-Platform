<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  LayoutDashboard,
  UserRound,
  Users,
  Building2,
  Wallet,
  Landmark,
  ClipboardCheck,
  CalendarDays,
  FileText,
  LogOut,
} from 'lucide-vue-next';

defineOptions({ name: 'SidebarNav' });

const props = defineProps({
  isMobile: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const route = useRoute();
const router = useRouter();

const navItems = [
  { label: 'Dashboard', to: '/dashboard/home', icon: LayoutDashboard },
  { label: 'Users', to: '/dashboard/users', icon: UserRound },
  { label: 'Employees', to: '/dashboard/employees', icon: Users },
  { label: 'Departments', to: '/dashboard/departments', icon: Building2 },
  { label: 'Tax Profiles', to: '/dashboard/tax-profiles', icon: Landmark },
  { label: 'Payroll', to: '/dashboard/payroll', icon: Wallet },
  { label: 'Attendance', to: '/dashboard/attendance', icon: ClipboardCheck },
  { label: 'Leaves', to: '/dashboard/leaves', icon: CalendarDays },
  { label: 'Payslips', to: '/dashboard/payslips', icon: FileText },
  { label: 'Profile', to: '/dashboard/profile', icon: FileText },
];

const isActive = (path) => route.path === path;

const logout = () => {
  router.push('/login');
  emit('close');
};

const activeLabel = computed(() => navItems.find((item) => isActive(item.to))?.label || 'Dashboard');

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
        v-for="item in navItems"
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
