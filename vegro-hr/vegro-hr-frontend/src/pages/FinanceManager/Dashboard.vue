<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApexCharts from 'vue3-apexcharts';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';
import { formatDate } from '../../utils/dateFormat';

defineOptions({ name: 'FinanceManagerDashboardPage' });

const router = useRouter();
const { hasPermission } = useAuth();

const isLoading = ref(true);
const errorMessage = ref('');
const isApproving = ref(false);

const payrolls = ref([]);
const payslips = ref([]);
const taxProfiles = ref([]);
const departmentEmployees = ref([]);
const signatureName = ref('');
const selectedPayrollId = ref(null);

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
    const requests = [
      apiClient.get('/api/payrolls', { params: { per_page: 1000 } }),
      apiClient.get('/api/payslips', { params: { per_page: 1000 } }),
      apiClient.get('/api/tax-profiles', { params: { per_page: 500 } }),
    ];

    if (hasPermission('employees.view')) {
      requests.push(apiClient.get('/api/employees/my-department'));
    }

    const responses = await Promise.all(requests);
    payrolls.value = unwrapList(responses[0]);
    payslips.value = unwrapList(responses[1]);
    taxProfiles.value = unwrapList(responses[2]);
    departmentEmployees.value = responses[3] ? unwrapList(responses[3]) : [];
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message ||
      'Unable to load finance manager dashboard metrics. Please try again.';
  } finally {
    isLoading.value = false;
  }
};

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

const pendingApprovalPayrolls = computed(() =>
  payrolls.value.filter((payroll) =>
    ['draft', 'processed'].includes(String(payroll?.status || '').toLowerCase()),
  ),
);

const approvedPayrolls = computed(() =>
  payrolls.value.filter((payroll) => String(payroll?.status || '').toLowerCase() === 'approved'),
);

const issuedPayslips = computed(() =>
  payslips.value.filter((payslip) => String(payslip?.status || '').toLowerCase() === 'issued'),
);

const taxProfileById = computed(() => {
  const map = new Map();
  taxProfiles.value.forEach((profile) => {
    if (profile?.id) {
      map.set(Number(profile.id), profile);
    }
  });
  return map;
});

const pendingBaseSummary = computed(() => {
  let total = 0;
  let baseCurrency = '';

  pendingApprovalPayrolls.value.forEach((payroll) => {
    const profile = taxProfileById.value.get(Number(payroll?.tax_profile_id));
    const rate = Number(profile?.exchange_rate_to_base || 1);
    total += Number(payroll?.net_salary ?? 0) * (rate > 0 ? rate : 1);
    if (!baseCurrency && /^[A-Z]{3}$/.test(String(profile?.base_currency || '').toUpperCase())) {
      baseCurrency = String(profile.base_currency).toUpperCase();
    }
  });

  return {
    total,
    currency: baseCurrency || dashboardCurrency.value,
  };
});

const statusChartSeries = computed(() => [
  pendingApprovalPayrolls.value.length,
  approvedPayrolls.value.length,
  issuedPayslips.value.length,
]);

const statusChartOptions = computed(() => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels: ['Pending Approval', 'Approved Payroll', 'Issued Payslips'],
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#f59e0b', '#34d399', '#38bdf8'],
}));

const stats = computed(() => [
  {
    label: 'Pending Payroll Approvals',
    value: pendingApprovalPayrolls.value.length.toLocaleString(),
    hint: 'Requires sign-off',
  },
  {
    label: 'Pending Net (Base)',
    value: pendingBaseSummary.value.total.toLocaleString('en-US', {
      style: 'currency',
      currency: pendingBaseSummary.value.currency,
      maximumFractionDigits: 0,
    }),
    hint: 'FX normalized amount',
  },
  {
    label: 'Approved This Cycle',
    value: approvedPayrolls.value.length.toLocaleString(),
    hint: 'Payroll records approved',
  },
  {
    label: 'Finance Department Team',
    value: departmentEmployees.value.length.toLocaleString(),
    hint: 'Direct visibility',
  },
]);

const queuePreview = computed(() => pendingApprovalPayrolls.value.slice(0, 5));

const quickActions = computed(() =>
  [
    {
      label: 'Open Payroll Queue',
      description: 'Review and finalize payroll runs',
      route: '/dashboard/payroll',
      permission: 'payroll.view',
    },
    {
      label: 'Open Payslips',
      description: 'Track approval and issuance',
      route: '/dashboard/payslips',
      permission: 'payslips.view',
    },
    {
      label: 'View Reports',
      description: 'Check finance and workforce analytics',
      route: '/dashboard/reports',
      permission: null,
    },
    {
      label: 'Department Team',
      description: 'See finance team members',
      route: '/dashboard/my-team',
      permission: 'employees.view',
    },
  ].filter((action) => !action.permission || hasPermission(action.permission)),
);

const startApprove = (payrollId) => {
  selectedPayrollId.value = payrollId;
  signatureName.value = '';
};

const cancelApprove = () => {
  selectedPayrollId.value = null;
  signatureName.value = '';
};

const approvePayroll = async () => {
  if (!selectedPayrollId.value || !signatureName.value.trim()) return;
  isApproving.value = true;
  errorMessage.value = '';

  try {
    await apiClient.post(`/api/payrolls/${selectedPayrollId.value}/approve`, {
      signature_name: signatureName.value.trim(),
    });
    cancelApprove();
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to approve payroll.';
  } finally {
    isApproving.value = false;
  }
};

const goTo = (route) => {
  router.push(route);
};

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
      <section class="flex flex-col gap-4">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Finance Manager Command Center
        </p>
        <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
          Approve payroll confidently and steer finance operations.
        </h1>
        <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
          Monitor approval queues, signed payroll records, and department execution from one dashboard.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <span v-if="isLoading" class="rounded-full border border-white/10 bg-white/5 px-3 py-1">
            Syncing finance manager data...
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
          <p class="text-xs text-slate-300/70">{{ stat.hint }}</p>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Approval workflow</p>
          <h3 class="mt-2 text-lg font-semibold">Status split</h3>
          <div class="mt-6">
            <ApexCharts
              type="donut"
              height="280"
              :options="statusChartOptions"
              :series="statusChartSeries"
            />
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Quick actions</h2>
          <p class="mt-2 text-sm text-slate-300/70">
            Open priority workflows for payroll, team, and reporting.
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
            <h2 class="text-lg font-semibold">Payroll approval queue</h2>
            <p class="mt-2 text-sm text-slate-300/70">
              Sign and approve pending payroll records directly from this view.
            </p>
          </div>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="goTo('/dashboard/payroll')"
          >
            Open payroll module
          </button>
        </div>

        <div class="mt-6 space-y-3">
          <div
            v-for="payroll in queuePreview"
            :key="payroll.id"
            class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="font-medium text-slate-100">
                  {{ payroll.employee?.name || `Employee #${payroll.employee_id}` }}
                </p>
                <p class="text-xs text-slate-400">
                  {{ payroll.month }}/{{ payroll.year }} ·
                  {{ Number(payroll.net_salary || 0).toLocaleString('en-US', {
                    style: 'currency',
                    currency: dashboardCurrency,
                    maximumFractionDigits: 0,
                  }) }}
                </p>
              </div>
              <button
                v-if="hasPermission('payroll.approve') && selectedPayrollId !== payroll.id"
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20"
                type="button"
                @click="startApprove(payroll.id)"
              >
                Approve with signature
              </button>
            </div>

            <div v-if="selectedPayrollId === payroll.id" class="mt-3 flex flex-wrap items-center gap-2">
              <input
                v-model="signatureName"
                type="text"
                class="h-10 min-w-[220px] flex-1 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-xs text-white outline-none focus:border-emerald-300/50 focus:ring-2 focus:ring-emerald-300/30"
                placeholder="Type your signature name"
              />
              <button
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
                type="button"
                :disabled="isApproving || !signatureName.trim()"
                @click="approvePayroll"
              >
                {{ isApproving ? 'Approving...' : 'Confirm' }}
              </button>
              <button
                class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                type="button"
                :disabled="isApproving"
                @click="cancelApprove"
              >
                Cancel
              </button>
            </div>
          </div>

          <p v-if="!queuePreview.length" class="text-sm text-slate-400">
            No payroll records are waiting for approval.
          </p>
        </div>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <h2 class="text-lg font-semibold">Recent signed approvals</h2>
        <p class="mt-2 text-sm text-slate-300/70">
          Latest payroll approvals with signature timestamp.
        </p>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Employee</th>
                <th class="px-3 py-2">Period</th>
                <th class="px-3 py-2">Signature</th>
                <th class="px-3 py-2">Approved on</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="payroll in approvedPayrolls.slice(0, 6)"
                :key="`approved-${payroll.id}`"
                class="border-t border-white/10 text-slate-200/90"
              >
                <td class="px-3 py-2">{{ payroll.employee?.name || `Employee #${payroll.employee_id}` }}</td>
                <td class="px-3 py-2">{{ payroll.month }}/{{ payroll.year }}</td>
                <td class="px-3 py-2">{{ payroll.approver_signature_name || '-' }}</td>
                <td class="px-3 py-2">{{ formatDate(payroll.approved_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>
