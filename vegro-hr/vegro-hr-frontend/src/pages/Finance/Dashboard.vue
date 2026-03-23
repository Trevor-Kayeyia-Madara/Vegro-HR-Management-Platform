<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApexCharts from 'vue3-apexcharts';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'FinanceDashboardPage' });

const router = useRouter();
const { hasPermission } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');

const payrolls = ref([]);
const payslips = ref([]);
const taxProfiles = ref([]);

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
    const [payrollResponse, payslipResponse, taxProfileResponse] = await Promise.all([
      apiClient.get('/api/payrolls', { params: { per_page: 1000 } }),
      apiClient.get('/api/payslips', { params: { per_page: 1500 } }),
      apiClient.get('/api/tax-profiles', { params: { per_page: 500 } }),
    ]);

    payrolls.value = unwrapList(payrollResponse);
    payslips.value = unwrapList(payslipResponse);
    taxProfiles.value = unwrapList(taxProfileResponse);
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load finance dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

const netPayrollTotal = computed(() =>
  payrolls.value.reduce((sum, payroll) => sum + Number(payroll?.net_salary ?? 0), 0),
);

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

const payrollStatusSeries = computed(() => {
  const statuses = ['draft', 'processed', 'paid'];
  return statuses.map(
    (status) =>
      payrolls.value.filter((payroll) => String(payroll?.status || '').toLowerCase() === status)
        .length,
  );
});

const payrollStatusOptions = computed(() => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels: ['Draft', 'Processed', 'Paid'],
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#38bdf8', '#f59e0b', '#34d399'],
}));

const stats = computed(() => [
  {
    label: 'Payroll Runs',
    value: payrolls.value.length.toLocaleString(),
    change: `${payslips.value.length.toLocaleString()} payslips issued`,
  },
  {
    label: 'Net Payroll',
    value: netPayrollTotal.value.toLocaleString('en-US', {
      style: 'currency',
      currency: dashboardCurrency.value,
      maximumFractionDigits: 0,
    }),
    change: 'Current month total',
  },
  {
    label: 'Tax Profiles',
    value: taxProfiles.value.length.toLocaleString(),
    change: 'Active tax setups',
  },
  {
    label: 'Pending Payroll',
    value: payrolls.value.filter((p) => String(p?.status || '').toLowerCase() === 'draft').length.toLocaleString(),
    change: 'Awaiting processing',
  },
]);

const quickActions = computed(() => [
  {
    label: 'Run Payroll',
    description: 'Process salary runs and deductions',
    route: '/dashboard/payroll',
    permission: 'payroll.view',
  },
  {
    label: 'Payslips',
    description: 'Issue and review payslips',
    route: '/dashboard/payslips',
    permission: 'payslips.view',
  },
  {
    label: 'Tax Profiles',
    description: 'Manage statutory tax profiles',
    route: '/dashboard/tax-profiles',
    permission: 'taxprofiles.view',
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
          Finance Command Center
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Control payroll, taxes, and payouts with precision.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Track payroll cycles, monitor payouts, and keep statutory setups aligned with every run.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing finance data...
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
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Payroll Status</p>
          <h3 class="mt-2 text-lg font-semibold">Run Progress</h3>
          <div class="mt-6">
            <ApexCharts
              type="donut"
              height="280"
              :options="payrollStatusOptions"
              :series="payrollStatusSeries"
            />
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Quick actions</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Finance workflows tailored for payroll execution.
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

