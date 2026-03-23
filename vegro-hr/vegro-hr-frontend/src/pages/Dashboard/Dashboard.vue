<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import ApexCharts from 'vue3-apexcharts';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'DashboardPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const { roleTitle } = useAuth();

const employees = ref([]);
const departments = ref([]);
const attendances = ref([]);
const leaveRequests = ref([]);
const payrolls = ref([]);
const taxProfiles = ref([]);

const today = computed(() => new Date().toLocaleDateString('en-CA'));

const attendanceToday = computed(() =>
  attendances.value.filter((attendance) => attendance?.date === today.value),
);

const attendanceOnTimeRate = computed(() => {
  const total = attendanceToday.value.length;
  if (!total) return '0% on time';
  const onTime = attendanceToday.value.filter((attendance) => attendance?.status === 'present').length;
  return `${Math.round((onTime / total) * 100)}% on time`;
});

const pendingLeaves = computed(() =>
  leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === 'pending'),
);

const payrollTotal = computed(() =>
  payrolls.value.reduce((sum, payroll) => {
    const value =
      Number(payroll?.amount) ||
      Number(payroll?.net_salary) ||
      Number(payroll?.basic_salary) ||
      0;
    return sum + value;
  }, 0),
);

const latestPayrollLabel = computed(() => {
  if (!payrolls.value.length) return 'No payroll runs yet';
  const sorted = [...payrolls.value].sort((a, b) => {
    const aYear = Number(a?.year || 0);
    const bYear = Number(b?.year || 0);
    if (aYear !== bYear) return bYear - aYear;
    const aMonth = Number(a?.month || 0);
    const bMonth = Number(b?.month || 0);
    return bMonth - aMonth;
  });
  const latest = sorted[0];
  if (latest?.month && latest?.year) {
    return `Latest run: ${latest.month}/${latest.year}`;
  }
  return 'Latest run recorded';
});

const dashboardCurrency = computed(() => {
  const byId = new Map(
    taxProfiles.value
      .filter((profile) => profile?.id && profile?.currency)
      .map((profile) => [Number(profile.id), String(profile.currency).toUpperCase()]),
  );

  const payrollCurrency = payrolls.value
    .map((payroll) => byId.get(Number(payroll?.tax_profile_id)))
    .find((currency) => /^[A-Z]{3}$/.test(currency || ''));

  if (payrollCurrency) return payrollCurrency;

  const profileCurrency = taxProfiles.value
    .map((profile) => String(profile?.currency || '').toUpperCase())
    .find((currency) => /^[A-Z]{3}$/.test(currency));

  return profileCurrency || 'USD';
});

const formatCurrency = (value) =>
  new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: dashboardCurrency.value,
    maximumFractionDigits: 0,
  }).format(value || 0);

const formatCompact = (value) =>
  new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(value || 0);

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
    const [
      employeesResponse,
      departmentsResponse,
      attendanceResponse,
      leaveResponse,
      payrollResponse,
      taxProfilesResponse,
    ] = await Promise.all([
      apiClient.get('/api/employees', { params: { per_page: 1000 } }),
      apiClient.get('/api/departments', { params: { per_page: 500 } }),
      apiClient.get('/api/attendances', { params: { per_page: 2000 } }),
      apiClient.get('/api/leave-requests/all', { params: { per_page: 1000 } }),
      apiClient.get('/api/payrolls', { params: { per_page: 1000 } }),
      apiClient.get('/api/tax-profiles', { params: { per_page: 1000 } }),
    ]);

    employees.value = unwrapList(employeesResponse);
    departments.value = unwrapList(departmentsResponse);
    attendances.value = unwrapList(attendanceResponse);
    leaveRequests.value = unwrapList(leaveResponse);
    payrolls.value = unwrapList(payrollResponse);
    taxProfiles.value = unwrapList(taxProfilesResponse);
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const stats = computed(() => [
  {
    label: 'Total Employees',
    value: employees.value.length.toLocaleString(),
    change: `${departments.value.length} departments active`,
  },
  {
    label: 'Departments',
    value: departments.value.length.toLocaleString(),
    change: 'Org structure synced',
  },
  {
    label: 'Attendance Today',
    value: attendanceToday.value.length.toLocaleString(),
    change: attendanceOnTimeRate.value,
  },
  {
    label: 'Pending Leaves',
    value: pendingLeaves.value.length.toLocaleString(),
    change: `${leaveRequests.value.length} total requests`,
  },
  {
    label: 'Payroll Summary',
    value: formatCurrency(payrollTotal.value),
    change: latestPayrollLabel.value,
  },
]);

const accessBadges = computed(() => {
  const map = {
    admin: ['Admin', 'Users', 'Settings', 'Payroll'],
    hr: ['HR', 'Employees', 'Leaves', 'Payroll'],
    finance: ['Finance', 'Payroll', 'Payslips'],
    manager: ['Manager', 'Approvals'],
    employee: ['Employee', 'My Profile', 'Leaves'],
  };
  return map[roleTitle.value] || ['Workspace'];
});

const payrollTrend = computed(() => {
  const grouped = new Map();
  payrolls.value.forEach((payroll) => {
    const month = Number(payroll?.month || 0);
    const year = Number(payroll?.year || 0);
    if (!month || !year) return;
    const key = `${year}-${String(month).padStart(2, '0')}`;
    const current = grouped.get(key) || { label: `${month}/${year}`, total: 0 };
    current.total += Number(payroll?.net_salary || payroll?.basic_salary || 0);
    grouped.set(key, current);
  });

  return Array.from(grouped.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .slice(-8)
    .map(([, value]) => value);
});

const payrollSeries = computed(() => [
  {
    name: 'Net Salary',
    data: payrollTrend.value.map((item) => Math.round(item.total)),
  },
]);

const payrollOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    height: 280,
    foreColor: '#cbd5f5',
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 },
  },
  dataLabels: { enabled: false },
  xaxis: {
    categories: payrollTrend.value.map((item) => item.label),
    labels: { style: { colors: '#94a3b8' } },
  },
  yaxis: {
    labels: {
      formatter: (value) => formatCompact(value),
      style: { colors: '#94a3b8' },
    },
  },
  grid: { borderColor: 'rgba(148,163,184,0.15)' },
  colors: ['#34d399'],
  tooltip: {
    theme: 'dark',
    y: { formatter: (value) => formatCurrency(value) },
  },
}));

const attendanceStatusSeries = computed(() => {
  const statuses = ['present', 'late', 'excused', 'absent'];
  return statuses.map(
    (status) => attendanceToday.value.filter((att) => att?.status === status).length,
  );
});

const attendanceStatusOptions = computed(() => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels: ['Present', 'Late', 'Excused', 'Absent'],
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#34d399', '#f59e0b', '#38bdf8', '#f87171'],
}));

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

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <section class="flex flex-col gap-4">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Vegro Command Center
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Enterprise workforce insights in one decisive dashboard.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Track workforce health, compliance, and payroll readiness across your enterprise.
          Metrics are streamed live from your operational data.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing data...
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
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-300/80">
          <span
            v-for="badge in accessBadges"
            :key="badge"
            class="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-3 py-1 text-emerald-200"
          >
            {{ badge }}
          </span>
        </div>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="stat in stats"
          :key="stat.label"
          class="flex h-full flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_20px_60px_rgba(15,23,42,0.5)]"
        >
          <div class="flex items-start justify-between">
            <p class="text-sm font-medium text-slate-200/80">{{ stat.label }}</p>
            <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">
              Live
            </span>
          </div>
          <div class="text-3xl font-semibold">{{ stat.value }}</div>
          <p class="text-xs text-slate-300/70">{{ stat.change }}</p>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Payroll Trend</p>
              <h2 class="mt-2 text-lg font-semibold">Net Salary Over Time</h2>
            </div>
            <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">
              Last 8 runs
            </span>
          </div>
          <div class="mt-6">
            <ApexCharts
              v-if="payrollSeries[0].data.length"
              type="area"
              height="280"
              :options="payrollOptions"
              :series="payrollSeries"
            />
            <div v-else class="rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-sm text-slate-300/70">
              No payroll data yet. Run payroll to see trends.
            </div>
          </div>
        </div>

        <div class="grid gap-6">
          <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Attendance Today</p>
            <h3 class="mt-2 text-lg font-semibold">Daily Status Split</h3>
            <div class="mt-6">
              <ApexCharts
                type="donut"
                height="260"
                :options="attendanceStatusOptions"
                :series="attendanceStatusSeries"
              />
            </div>
          </div>
          <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leave Pipeline</p>
            <h3 class="mt-2 text-lg font-semibold">Request Status</h3>
            <div class="mt-6">
              <ApexCharts
                type="donut"
                height="260"
                :options="leaveStatusOptions"
                :series="leaveStatusSeries"
              />
            </div>
          </div>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Today at a glance</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Attendance, leave requests, and payroll approvals are now streamed from your operations layer.
          </p>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Critical Tasks</p>
              <p class="mt-3 text-2xl font-semibold">
                {{ payrolls.length.toLocaleString() }}
              </p>
              <p class="text-xs text-slate-400/80">Payroll records indexed</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leave Queue</p>
              <p class="mt-3 text-2xl font-semibold">
                {{ pendingLeaves.length.toLocaleString() }}
              </p>
              <p class="text-xs text-slate-400/80">Awaiting manager review</p>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Operational readiness</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Stay ahead of compliance deadlines and workforce changes.
          </p>
          <div class="mt-6 flex flex-col gap-4 text-sm">
            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3">
              <span>Active departments</span>
              <span class="text-amber-200">{{ departments.length.toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3">
              <span>Attendance recorded today</span>
              <span class="text-emerald-200">{{ attendanceToday.length.toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3">
              <span>Employees on file</span>
              <span class="text-sky-200">{{ employees.length.toLocaleString() }}</span>
            </div>
          </div>
          <RouterLink
            to="/dashboard/reports"
            class="mt-6 inline-flex h-10 items-center justify-center rounded-xl border border-emerald-300/40 bg-emerald-300/10 px-4 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          >
            View reports
          </RouterLink>
        </div>
      </section>

    </div>
  </div>
</template>

