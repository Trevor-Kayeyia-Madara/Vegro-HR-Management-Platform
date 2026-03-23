<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '../../api/apiClient';
import employeeService from '../../services/employeeService';
import useAuth from '../../hooks/useAuth';
import { formatDateRange } from '../../utils/dateFormat';

defineOptions({ name: 'EmployeeDashboardPage' });

const router = useRouter();
const { hasPermission, user } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');
const leaveRequests = ref([]);
const payslips = ref([]);
const employeeRecord = ref(null);
const leaveSummary = ref(null);
const profile = computed(() => user.value);

const unwrapList = (response) => {
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

const loadDashboard = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [leavesResponse, payslipsResponse, employeesResponse] = await Promise.all([
      apiClient.get('/api/leave-requests', { params: { per_page: 50 } }),
      apiClient.get('/api/payslips/me', { params: { per_page: 20 } }),
      employeeService.getEmployees({ per_page: 1 }),
    ]);

    leaveRequests.value = unwrapList(leavesResponse);
    payslips.value = unwrapList(payslipsResponse);
    employeeRecord.value = unwrapList(employeesResponse)[0] || null;

    if (employeeRecord.value?.id) {
      const leaveSummaryResponse = await employeeService.getEmployeeLeaveSummary(employeeRecord.value.id);
      leaveSummary.value = leaveSummaryResponse?.data?.data ?? leaveSummaryResponse?.data ?? null;
    } else {
      leaveSummary.value = null;
    }
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load your dashboard. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const pendingLeaves = computed(() =>
  leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === 'pending'),
);

const approvedLeaves = computed(() =>
  leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === 'approved'),
);

const latestPayslip = computed(() => payslips.value[0]);

const leaveHistory = computed(() => leaveRequests.value.slice(0, 5));

const stats = computed(() => [
  {
    label: 'Pending Leaves',
    value: pendingLeaves.value.length.toLocaleString(),
    change: 'Awaiting approval',
  },
  {
    label: 'Approved Leaves',
    value: approvedLeaves.value.length.toLocaleString(),
    change: 'Upcoming time off',
  },
  {
    label: 'Payslips',
    value: payslips.value.length.toLocaleString(),
    change: latestPayslip.value ? 'Latest ready' : 'No payslips yet',
  },
  {
    label: 'Total Leave Days',
    value: Number(leaveSummary.value?.total_leave_days ?? 0).toFixed(1),
    change: `${Number(leaveSummary.value?.leave_days_taken ?? 0).toFixed(1)} taken`,
  },
  {
    label: 'Leave Balance',
    value: Number(leaveSummary.value?.leave_balance ?? 0).toFixed(1),
    change: `Carry forward ${Number(leaveSummary.value?.annual?.carry_forward_days ?? 0).toFixed(1)} days`,
  },
]);

const quickActions = computed(() => [
  {
    label: 'Request Leave',
    description: 'Submit new leave request',
    route: '/dashboard/leaves',
    permission: 'leaves.view',
  },
  {
    label: 'My Payslips',
    description: 'View recent payslips',
    route: '/dashboard/payslips',
    permission: 'payslips.view',
  },
  {
    label: 'Profile',
    description: 'Review your details',
    route: '/dashboard/profile',
    permission: 'profile.view',
  },
].filter((action) => hasPermission(action.permission)));

const goTo = (route) => {
  router.push(route);
};

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <section class="flex flex-col gap-4">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Employee Workspace
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Your work life, organized.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Track leave requests, payslips, and profile updates in one place.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing your data...
          </span>
          <span v-else class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-emerald-200">
            Updated just now
          </span>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 transition hover:bg-white/10"
            type="button"
            @click="loadDashboard"
          >
            Refresh
          </button>
        </div>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="stat in stats"
          :key="stat.label"
          class="flex h-full flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.5)]"
        >
          <p class="text-sm font-medium text-slate-200/80">{{ stat.label }}</p>
          <div class="text-3xl font-semibold">{{ stat.value }}</div>
          <p class="text-xs text-slate-300/70">{{ stat.change }}</p>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Latest payslip</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            A quick snapshot of your most recent payroll statement.
          </p>
          <div class="mt-6 rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm">
            <div class="flex items-center justify-between">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Period</p>
              <span class="text-xs text-slate-300/70">
                {{ latestPayslip?.payroll?.month || '-' }} {{ latestPayslip?.payroll?.year || '' }}
              </span>
            </div>
            <div class="mt-4 flex items-center justify-between">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Net pay</p>
              <span class="text-base font-semibold text-emerald-200">
                {{ latestPayslip?.net_pay ?? latestPayslip?.payroll?.net_salary ?? '-' }}
              </span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-300/70">
              <span>Status</span>
              <span class="rounded-full border border-white/10 px-3 py-1 uppercase tracking-[0.2em]">
                {{ latestPayslip?.status || 'draft' }}
              </span>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Quick actions</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Jump back into the tools you use most.
          </p>
          <div class="mt-6 flex flex-col gap-3">
            <button
              v-for="action in quickActions"
              :key="action.label"
              class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm transition hover:bg-white/10"
              type="button"
              @click="goTo(action.route)"
            >
              <div>
                <p class="font-medium text-slate-100">{{ action.label }}</p>
                <p class="text-xs text-slate-400">{{ action.description }}</p>
              </div>
              <span class="text-xs text-emerald-200">Open</span>
            </button>
          </div>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Profile snapshot</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Your primary details on file.
          </p>
          <div class="mt-6 grid gap-4 text-sm">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Name</p>
              <p class="mt-2 text-base font-semibold">{{ profile?.name || 'â€”' }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Email</p>
              <p class="mt-2 text-base font-semibold">{{ profile?.email || 'â€”' }}</p>
            </div>
                        <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Role</p>
              <p class="mt-2 text-base font-semibold">{{ profile?.role?.title || 'Employee' }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Annual Leave</p>
              <p class="mt-2 text-base font-semibold">
                {{ Number(leaveSummary?.annual?.balance_days ?? 0).toFixed(1) }}
                /
                {{ Number(leaveSummary?.annual?.entitled_days ?? 0).toFixed(1) }}
              </p>
              <p class="mt-1 text-xs text-slate-300/70">
                {{ Number(leaveSummary?.annual?.used_days ?? 0).toFixed(1) }} used Â·
                Carry {{ Number(leaveSummary?.annual?.carry_forward_days ?? 0).toFixed(1) }}
              </p>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Recent leave requests</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Track the latest leave submissions and status updates.
          </p>
          <div class="mt-6 space-y-3">
            <div
              v-for="leave in leaveHistory"
              :key="leave.id"
              class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm"
            >
              <div>
                <p class="font-medium text-slate-100">
                  {{ leave.leave_type || 'Leave request' }}
                </p>
                <p class="text-xs text-slate-400">
                  {{ formatDateRange(leave.start_date, leave.end_date, '—') }}
                </p>
              </div>
              <span
                class="rounded-full border border-white/10 px-3 py-1 text-xs uppercase tracking-[0.2em]"
                :class="leave.status === 'approved'
                  ? 'border-emerald-300/40 text-emerald-200'
                  : leave.status === 'rejected'
                    ? 'border-rose-400/40 text-rose-200'
                    : 'border-white/20 text-slate-300/70'"
              >
                {{ leave.status || 'pending' }}
              </span>
            </div>
            <p v-if="!leaveHistory.length" class="text-sm text-slate-400">
              No leave requests yet. Create one when needed.
            </p>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>




