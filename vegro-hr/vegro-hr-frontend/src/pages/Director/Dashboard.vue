<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApexCharts from 'vue3-apexcharts';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'DirectorDashboardPage' });

const router = useRouter();
const { hasPermission } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');

const payrolls = ref([]);
const payslips = ref([]);
const leaveRequests = ref([]);

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
    const [payrollResponse, payslipResponse, leaveResponse] = await Promise.all([
      apiClient.get('/api/payrolls', { params: { per_page: 1000 } }),
      apiClient.get('/api/payslips', { params: { per_page: 1000 } }),
      apiClient.get('/api/leave-requests/all', { params: { per_page: 1000 } }),
    ]);

    payrolls.value = unwrapList(payrollResponse);
    payslips.value = unwrapList(payslipResponse);
    leaveRequests.value = unwrapList(leaveResponse);
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load executive dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const payrollTotal = computed(() =>
  payrolls.value.reduce((sum, payroll) => sum + Number(payroll?.net_salary ?? 0), 0),
);

const leaveStatusSeries = computed(() => {
  const statuses = ['pending', 'approved', 'rejected'];
  return statuses.map(
    (status) =>
      leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === status)
        .length,
  );
});

const leaveStatusOptions = computed(() => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels: ['Pending', 'Approved', 'Rejected'],
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#f59e0b', '#34d399', '#f87171'],
}));

const stats = computed(() => [
  {
    label: 'Payroll runs',
    value: payrolls.value.length.toLocaleString(),
    change: 'Across the company',
  },
  {
    label: 'Net payroll',
    value: payrollTotal.value.toLocaleString('en-US', {
      style: 'currency',
      currency: 'KES',
      maximumFractionDigits: 0,
    }),
    change: 'Current period total',
  },
  {
    label: 'Payslips issued',
    value: payslips.value.length.toLocaleString(),
    change: 'Total slips generated',
  },
  {
    label: 'Leave requests',
    value: leaveRequests.value.length.toLocaleString(),
    change: 'Company-wide',
  },
]);

const quickActions = computed(() => [
  {
    label: 'Review Leave Requests',
    description: 'Approve or reject leaves',
    route: '/dashboard/leaves',
    permission: 'leaves.approve',
  },
  {
    label: 'Payroll Overview',
    description: 'Track payroll runs',
    route: '/dashboard/payroll',
    permission: 'payroll.view',
  },
  {
    label: 'Payslips',
    description: 'View issued payslips',
    route: '/dashboard/payslips',
    permission: 'payslips.view',
  },
].filter((action) => hasPermission(action.permission)));

const goTo = (route) => {
  router.push(route);
};

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <section class="flex flex-col gap-4">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Executive Overview
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Executive visibility across people and payroll.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Monitor payroll impact, leave risk, and workforce signals at a glance.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing executive metrics...
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
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leave pipeline</p>
          <h3 class="mt-2 text-lg font-semibold">Approval status</h3>
          <div class="mt-6">
            <ApexCharts
              type="donut"
              height="280"
              :options="leaveStatusOptions"
              :series="leaveStatusSeries"
            />
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Quick actions</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Executive workflows for approvals and oversight.
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
    </div>
  </div>
</template>
