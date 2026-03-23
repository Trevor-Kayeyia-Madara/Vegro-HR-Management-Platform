<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApexCharts from 'vue3-apexcharts';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';
import { formatDateRange } from '../../utils/dateFormat';

defineOptions({ name: 'ManagerDashboardPage' });

const router = useRouter();
const { hasPermission } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');

const pendingLeaves = ref([]);
const approvedLeaves = ref([]);
const teamLeaves = ref([]);
const isUpdating = ref(false);

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
    const [pendingResponse, approvedResponse, allResponse] = await Promise.all([
      apiClient.get('/api/leave-requests/pending', { params: { per_page: 1000 } }),
      apiClient.get('/api/leave-requests/approved', { params: { per_page: 1000 } }),
      apiClient.get('/api/leave-requests/all', { params: { per_page: 1000 } }),
    ]);

    pendingLeaves.value = unwrapList(pendingResponse);
    approvedLeaves.value = unwrapList(approvedResponse);
    teamLeaves.value = unwrapList(allResponse);
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load manager dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const updateStatus = async (leave, status) => {
  if (!leave?.id || !hasPermission('leaves.approve')) return;
  isUpdating.value = true;
  try {
    if (status === 'approved') {
      await apiClient.post(`/api/leave-requests/${leave.id}/approve`);
    } else {
      await apiClient.post(`/api/leave-requests/${leave.id}/reject`);
    }
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update leave status.';
  } finally {
    isUpdating.value = false;
  }
};

const pendingPreview = computed(() => pendingLeaves.value.slice(0, 5));

const leaveStatusSeries = computed(() => [
  pendingLeaves.value.length,
  approvedLeaves.value.length,
  Math.max(teamLeaves.value.length - pendingLeaves.value.length - approvedLeaves.value.length, 0),
]);

const leaveStatusOptions = computed(() => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels: ['Pending', 'Approved', 'Other'],
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#f59e0b', '#34d399', '#94a3b8'],
}));

const stats = computed(() => [
  {
    label: 'Pending approvals',
    value: pendingLeaves.value.length.toLocaleString(),
    change: 'Awaiting your action',
  },
  {
    label: 'Approved leaves',
    value: approvedLeaves.value.length.toLocaleString(),
    change: 'In your department',
  },
  {
    label: 'Total requests',
    value: teamLeaves.value.length.toLocaleString(),
    change: 'Across the team',
  },
]);

const quickActions = computed(() => [
  {
    label: 'Review Leave Requests',
    description: 'Approve or reject pending items',
    route: '/dashboard/leaves',
    permission: 'leaves.approve',
  },
  {
    label: 'Team Attendance',
    description: 'Monitor daily attendance',
    route: '/dashboard/attendance',
    permission: 'attendance.view',
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
          Manager Command Center
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Keep your team aligned and on track.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Review leave requests and manage team coverage with live visibility.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing team data...
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

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
          <h3 class="mt-2 text-lg font-semibold">Status overview</h3>
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
            Jump back into the workflows you own.
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

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold">Pending approvals</h2>
            <p class="mt-2 text-sm text-slate-300/70">
              Approve or reject pending leave requests for your team.
            </p>
          </div>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="goTo('/dashboard/leaves')"
          >
            Open leaves
          </button>
        </div>
        <div class="mt-6 space-y-3">
          <div
            v-for="leave in pendingPreview"
            :key="leave.id"
            class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm"
          >
            <div>
              <p class="font-medium text-slate-100">
                {{ leave.employee?.name || `Employee #${leave.employee_id}` }}
              </p>
              <p class="text-xs text-slate-400">
                {{ formatDateRange(leave.start_date, leave.end_date) }} · {{ leave.type || 'annual' }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
                type="button"
                :disabled="isUpdating"
                @click="updateStatus(leave, 'approved')"
              >
                Approve
              </button>
              <button
                class="rounded-full border border-rose-400/40 bg-rose-400/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-400/20 disabled:opacity-60"
                type="button"
                :disabled="isUpdating"
                @click="updateStatus(leave, 'rejected')"
              >
                Reject
              </button>
            </div>
          </div>
          <p v-if="!pendingPreview.length" class="text-sm text-slate-400">
            No pending requests right now.
          </p>
        </div>
      </section>
    </div>
  </div>
</template>




