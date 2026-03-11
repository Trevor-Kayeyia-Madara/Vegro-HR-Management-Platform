<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import ApexCharts from 'vue3-apexcharts';
import { useRouter } from 'vue-router';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'HrDashboardPage' });

const router = useRouter();
const { hasPermission } = useAuth();
const isLoading = ref(true);
const errorMessage = ref('');

const employees = ref([]);
const departments = ref([]);
const attendances = ref([]);
const leaveRequests = ref([]);

const today = computed(() => new Date().toLocaleDateString('en-CA'));

const attendanceToday = computed(() =>
  attendances.value.filter((attendance) => attendance?.date === today.value),
);

const pendingLeaves = computed(() =>
  leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === 'pending'),
);

const approvedLeaves = computed(() =>
  leaveRequests.value.filter((leave) => String(leave?.status || '').toLowerCase() === 'approved'),
);

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
    const [employeesResponse, departmentsResponse, attendanceResponse, leaveResponse] =
      await Promise.all([
        apiClient.get('/api/employees', { params: { per_page: 1000 } }),
        apiClient.get('/api/departments', { params: { per_page: 500 } }),
        apiClient.get('/api/attendances', { params: { per_page: 2000 } }),
        apiClient.get('/api/leave-requests/all', { params: { per_page: 1000 } }),
      ]);

    employees.value = unwrapList(employeesResponse);
    departments.value = unwrapList(departmentsResponse);
    attendances.value = unwrapList(attendanceResponse);
    leaveRequests.value = unwrapList(leaveResponse);
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load HR dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const stats = computed(() => [
  {
    label: 'Active Employees',
    value: employees.value.length.toLocaleString(),
    change: `${departments.value.length} org units`,
  },
  {
    label: 'Attendance Today',
    value: attendanceToday.value.length.toLocaleString(),
    change: 'Daily check-ins',
  },
  {
    label: 'Pending Leaves',
    value: pendingLeaves.value.length.toLocaleString(),
    change: `${leaveRequests.value.length} total requests`,
  },
  {
    label: 'Approved Leaves',
    value: approvedLeaves.value.length.toLocaleString(),
    change: 'Ready for coverage planning',
  },
]);

const quickActions = computed(() => [
  {
    label: 'Employee Directory',
    description: 'Manage workforce profiles',
    route: '/dashboard/employees',
    permission: 'employees.view',
  },
  {
    label: 'Attendance',
    description: 'Review daily logs',
    route: '/dashboard/attendance',
    permission: 'attendance.view',
  },
  {
    label: 'Leave Requests',
    description: 'Approve or reject requests',
    route: '/dashboard/leaves',
    permission: 'leaves.view',
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
          HR Command Center
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Real-time workforce operations at your fingertips.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Monitor attendance, leave requests, and employee coverage across the enterprise.
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

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Attendance Today</p>
          <h3 class="mt-2 text-lg font-semibold">Daily Status Split</h3>
          <div class="mt-6">
            <ApexCharts
              type="donut"
              height="280"
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
              height="280"
              :options="leaveStatusOptions"
              :series="leaveStatusSeries"
            />
          </div>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">HR priorities</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Focus areas for today based on live workforce activity.
          </p>
          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Pending leaves</p>
              <p class="mt-3 text-2xl font-semibold">{{ pendingLeaves.length.toLocaleString() }}</p>
              <p class="text-xs text-slate-400/80">Awaiting approval</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Attendance anomalies</p>
              <p class="mt-3 text-2xl font-semibold">
                {{ attendanceToday.filter((att) => att?.status === 'late' || att?.status === 'absent').length.toLocaleString() }}
              </p>
              <p class="text-xs text-slate-400/80">Late or absent entries</p>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Quick actions</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Jump to the workflows HR teams use most.
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
