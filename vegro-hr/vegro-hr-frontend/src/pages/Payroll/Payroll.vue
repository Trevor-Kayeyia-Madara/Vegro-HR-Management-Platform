<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import ApexCharts from 'vue3-apexcharts';

defineOptions({ name: 'PayrollPage' });

const payrolls = ref([]);
const chartPayrolls = ref([]);
const employees = ref([]);
const taxProfiles = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activePayroll = ref(null);
const isSubmitting = ref(false);

const searchQuery = ref('');
const pageSize = ref(8);
const currentPage = ref(1);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: pageSize.value,
  total: 0,
});

const form = ref({
  employee_id: '',
  tax_profile_id: '',
  month: '',
  year: '',
  basic_salary: '',
  allowances: '',
  deductions: '',
  tax: '',
  insurance_premium: '',
  pension_contribution: '',
  mortgage_interest: '',
});

const monthLabel = (value) => {
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
  return months[Number(value) - 1] || '—';
};

const formatCurrency = (value) =>
  new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(value || 0);

const formatCompact = (value) =>
  new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(value || 0);

const unwrapList = (response) => {
  if (Array.isArray(response?.data)) return response.data;
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

const parsePaginated = (response) => {
  const payload = response?.data?.data ?? response?.data;
  if (payload && Array.isArray(payload.data)) {
    const metaSource = payload.meta ?? payload;
    return {
      items: payload.data,
      meta: {
        current_page: metaSource.current_page ?? 1,
        last_page: metaSource.last_page ?? 1,
        per_page: metaSource.per_page ?? pageSize.value,
        total: metaSource.total ?? payload.data.length,
      },
    };
  }
  if (Array.isArray(payload)) {
    return {
      items: payload,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: payload.length || pageSize.value,
        total: payload.length,
      },
    };
  }
  return {
    items: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: pageSize.value,
      total: 0,
    },
  };
};

const loadPayrolls = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [payrollResponse, employeeResponse, profileResponse, chartResponse] = await Promise.all([
      apiClient.get('/api/payrolls', {
        params: { page: currentPage.value, per_page: pageSize.value },
      }),
      apiClient.get('/api/employees', { params: { per_page: 1000 } }),
      apiClient.get('/api/tax-profiles', { params: { per_page: 200 } }),
      apiClient.get('/api/payrolls', { params: { per_page: 1000 } }),
    ]);
    const parsed = parsePaginated(payrollResponse);
    payrolls.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    employees.value = unwrapList(employeeResponse);
    taxProfiles.value = unwrapList(profileResponse);
    chartPayrolls.value = unwrapList(chartResponse);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load payrolls.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activePayroll.value = null;
    form.value = {
      employee_id: '',
      tax_profile_id: '',
      month: '',
      year: new Date().getFullYear(),
      basic_salary: '',
      allowances: '',
      deductions: '',
      tax: '',
      insurance_premium: '',
      pension_contribution: '',
      mortgage_interest: '',
    };
  isModalOpen.value = true;
};

const openEdit = (payroll) => {
  modalMode.value = 'edit';
  activePayroll.value = payroll;
    form.value = {
      employee_id: payroll?.employee_id || '',
      tax_profile_id: payroll?.tax_profile_id || '',
      month: payroll?.month || '',
      year: payroll?.year || new Date().getFullYear(),
      basic_salary: payroll?.basic_salary || '',
      allowances: payroll?.allowances || '',
      deductions: payroll?.deductions || '',
      tax: payroll?.tax || '',
      insurance_premium: payroll?.insurance_premium || '',
      pension_contribution: payroll?.pension_contribution || '',
      mortgage_interest: payroll?.mortgage_interest || '',
    };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const payload = {
      employee_id: Number(form.value.employee_id),
      tax_profile_id: form.value.tax_profile_id ? Number(form.value.tax_profile_id) : null,
      month: Number(form.value.month),
      year: Number(form.value.year),
      basic_salary: Number(form.value.basic_salary),
      allowances: form.value.allowances ? Number(form.value.allowances) : null,
      deductions: form.value.deductions ? Number(form.value.deductions) : null,
      tax: form.value.tax ? Number(form.value.tax) : null,
      insurance_premium: form.value.insurance_premium ? Number(form.value.insurance_premium) : null,
      pension_contribution: form.value.pension_contribution ? Number(form.value.pension_contribution) : null,
      mortgage_interest: form.value.mortgage_interest ? Number(form.value.mortgage_interest) : null,
    };

    if (modalMode.value === 'create') {
      await apiClient.post('/api/payrolls', payload);
    } else if (activePayroll.value?.id) {
      await apiClient.put(`/api/payrolls/${activePayroll.value.id}`, payload);
    }

    await loadPayrolls();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save payroll.';
  } finally {
    isSubmitting.value = false;
  }
};

const deletePayroll = async (payroll) => {
  const confirmed = window.confirm('Delete this payroll record?');
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/payrolls/${payroll.id}`);
    await loadPayrolls();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete payroll.';
  }
};

const filteredPayrolls = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return payrolls.value;
  return payrolls.value.filter((payroll) => {
    const employeeName = payroll?.employee?.name?.toLowerCase() || '';
    const period = `${payroll?.month || ''}/${payroll?.year || ''}`;
    return employeeName.includes(query) || period.includes(query);
  });
});

const payrollTrend = computed(() => {
  const grouped = new Map();
  chartPayrolls.value.forEach((payroll) => {
    const month = Number(payroll?.month || 0);
    const year = Number(payroll?.year || 0);
    if (!month || !year) return;
    const key = `${year}-${String(month).padStart(2, '0')}`;
    const current = grouped.get(key) || {
      label: `${monthLabel(month)} ${year}`,
      gross: 0,
      net: 0,
    };
    const gross = Number(payroll?.gross_salary || payroll?.basic_salary || 0);
    const net = Number(payroll?.net_salary || 0);
    current.gross += gross;
    current.net += net;
    grouped.set(key, current);
  });

  return Array.from(grouped.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .slice(-8)
    .map(([, value]) => value);
});

const payrollChartSeries = computed(() => [
  { name: 'Gross', data: payrollTrend.value.map((item) => Math.round(item.gross)) },
  { name: 'Net', data: payrollTrend.value.map((item) => Math.round(item.net)) },
]);

const payrollChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 280,
    toolbar: { show: false },
    foreColor: '#cbd5f5',
  },
  plotOptions: {
    bar: { columnWidth: '45%', borderRadius: 6 },
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
  colors: ['#60a5fa', '#34d399'],
  tooltip: {
    theme: 'dark',
    y: { formatter: (value) => formatCurrency(value) },
  },
  legend: { labels: { colors: '#94a3b8' } },
}));

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadPayrolls();
};

onMounted(loadPayrolls);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Payroll</p>
          <h1 class="text-3xl font-semibold">Payroll Ledger</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Track payroll runs, allowances, deductions, and net salary totals.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search payroll..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add payroll
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Payroll Trend</p>
            <h2 class="mt-2 text-lg font-semibold">Gross vs Net Salary</h2>
          </div>
          <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">
            Last 8 runs
          </span>
        </div>
        <div class="mt-6">
          <ApexCharts
            v-if="payrollChartSeries[0].data.length"
            type="bar"
            height="280"
            :options="payrollChartOptions"
            :series="payrollChartSeries"
          />
          <div v-else class="rounded-2xl border border-white/10 bg-slate-950/40 p-6 text-sm text-slate-300/70">
            No payroll data yet. Create payroll entries to see trends.
          </div>
        </div>
      </section>

      <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="max-h-130 overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-275 w-full text-left text-xs sm:text-sm">
            <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="px-6 py-4 font-medium">Employee</th>
                <th class="px-6 py-4 font-medium">Period</th>
                <th class="px-6 py-4 font-medium">Basic</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Allowances</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">NSSF</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">SHIF</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Housing</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Deductions</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">PAYE</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Tax Rate</th>
                <th class="px-6 py-4 font-medium">Net</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="12">
                  Loading payrolls...
                </td>
              </tr>
              <tr
                v-for="payroll in filteredPayrolls"
                :key="payroll.id"
                class="hover:bg-white/5"
              >
                <td class="px-6 py-4 text-slate-100">
                  {{ payroll.employee?.name || `Employee #${payroll.employee_id}` }}
                </td>
                <td class="px-6 py-4 text-slate-300/80">
                  {{ monthLabel(payroll.month) }} {{ payroll.year }}
                </td>
                <td class="px-6 py-4 text-slate-300/70">
                  {{ payroll.basic_salary ? Number(payroll.basic_salary).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.allowances ? Number(payroll.allowances).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.nssf ? Number(payroll.nssf).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.shif ? Number(payroll.shif).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.housing_levy ? Number(payroll.housing_levy).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.deductions ? Number(payroll.deductions).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.paye ? Number(payroll.paye).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
                  {{ payroll.tax_rate ? `${Number(payroll.tax_rate).toFixed(2)}%` : '—' }}
                </td>
                <td class="px-6 py-4 text-emerald-200">
                  {{ payroll.net_salary ? Number(payroll.net_salary).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(payroll)"
                    >
                      Edit
                    </button>
                    <button
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deletePayroll(payroll)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredPayrolls.length">
                <td class="px-6 py-6 text-center text-slate-400" colspan="12">
                  No payroll records found yet.
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing
          {{ filteredPayrolls.length }}
          of
          {{ pagination.total }}
          payrolls
        </span>
        <div class="flex items-center gap-2">
          <button
            class="rounded-full border border-white/10 px-3 py-1 transition hover:bg-white/10 disabled:opacity-50"
            type="button"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
          >
            Prev
          </button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button
            class="rounded-full border border-white/10 px-3 py-1 transition hover:bg-white/10 disabled:opacity-50"
            type="button"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="isModalOpen"
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm"
        @click="closeModal"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="w-full max-w-xl rounded-3xl border border-white/10 bg-slate-950 p-6 text-white shadow-[0_30px_90px_rgba(15,23,42,0.75)]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Payroll
              </p>
              <h2 class="text-2xl font-semibold">
                {{ modalMode === 'create' ? 'New Payroll' : 'Update Payroll' }}
              </h2>
            </div>
            <button
              class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200"
              type="button"
              @click="closeModal"
            >
              Close
            </button>
          </div>

          <form class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Employee</span>
              <select
                v-model="form.employee_id"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name || `Employee #${employee.id}` }}
                </option>
              </select>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Tax Profile</span>
              <select
                v-model="form.tax_profile_id"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="">Default profile</option>
                <option v-for="profile in taxProfiles" :key="profile.id" :value="profile.id">
                  {{ profile.name }} ({{ profile.country_code }})
                </option>
              </select>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Month</span>
              <select
                v-model="form.month"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select month</option>
                <option v-for="month in 12" :key="month" :value="month">
                  {{ monthLabel(month) }}
                </option>
              </select>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Year</span>
              <input
                v-model="form.year"
                type="number"
                min="2000"
                max="2100"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Basic salary</span>
              <input
                v-model="form.basic_salary"
                type="number"
                min="0"
                step="0.01"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Allowances</span>
              <input
                v-model="form.allowances"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Other Deductions</span>
              <input
                v-model="form.deductions"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Insurance Premium</span>
              <input
                v-model="form.insurance_premium"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Pension Contribution</span>
              <input
                v-model="form.pension_contribution"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Mortgage Interest</span>
              <input
                v-model="form.mortgage_interest"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>

            <button
              class="sm:col-span-2 mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save payroll' }}
            </button>
          </form>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}
</style>


