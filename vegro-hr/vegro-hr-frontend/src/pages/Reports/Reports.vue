<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import { formatByField } from '../../utils/dateFormat';

defineOptions({ name: 'ReportsPage' });

const isLoading = ref(false);
const errorMessage = ref('');
const sources = ref([]);
const savedReports = ref([]);
const results = ref({ columns: [], rows: [] });

const form = ref({
  name: '',
  description: '',
  source: '',
  columns: [],
  filters: [],
  sort: { field: '', direction: 'desc' },
  limit: 500,
  is_shared: false,
});

const activeSource = computed(() => sources.value.find((item) => item.key === form.value.source));
const availableFields = computed(() => activeSource.value?.fields || []);

const presets = [
  {
    key: 'headcount_by_status',
    label: 'Headcount by Status',
    description: 'Active vs inactive employees',
    payload: {
      source: 'employees',
      columns: ['id', 'name', 'department_id', 'position', 'status', 'hire_date'],
      filters: [],
      sort: { field: 'status', direction: 'asc' },
      limit: 500,
      name: 'Headcount by Status',
      descriptionText: 'Employee status snapshot',
    },
  },
  {
    key: 'payroll_costs',
    label: 'Payroll Cost Overview',
    description: 'Gross vs net payroll',
    payload: {
      source: 'payrolls',
      columns: ['employee_id', 'month', 'year', 'gross_salary', 'tax', 'deductions', 'net_salary'],
      filters: [],
      sort: { field: 'year', direction: 'desc' },
      limit: 500,
      name: 'Payroll Cost Overview',
      descriptionText: 'Payroll totals by period',
    },
  },
  {
    key: 'attendance_status',
    label: 'Attendance Status',
    description: 'Attendance breakdown',
    payload: {
      source: 'attendances',
      columns: ['employee_id', 'date', 'status', 'clock_in', 'clock_out'],
      filters: [],
      sort: { field: 'date', direction: 'desc' },
      limit: 500,
      name: 'Attendance Status',
      descriptionText: 'Attendance trends by day',
    },
  },
  {
    key: 'leave_utilization',
    label: 'Leave Utilization',
    description: 'Approved vs pending leaves',
    payload: {
      source: 'leave_requests',
      columns: ['employee_id', 'type', 'start_date', 'end_date', 'leave_days', 'status'],
      filters: [],
      sort: { field: 'start_date', direction: 'desc' },
      limit: 500,
      name: 'Leave Utilization',
      descriptionText: 'Leave usage by type and status',
    },
  },
  {
    key: 'employee_leave_balances',
    label: 'Employee Leave Balances',
    description: 'Annual leave allocation vs used/balance',
    payload: {
      source: 'employees',
      columns: ['id', 'name', 'department_name', 'status', 'annual_leave_days', 'annual_leave_used', 'annual_leave_balance'],
      filters: [],
      sort: { field: 'annual_leave_balance', direction: 'asc' },
      limit: 500,
      name: 'Employee Leave Balances',
      descriptionText: 'Annual leave days, used, and balance per employee',
    },
  },
  {
    key: 'leaves_by_status',
    label: 'Leaves by Status',
    description: 'Totals grouped by status (SQL group by)',
    payload: {
      source: 'leaves_by_status',
      columns: ['status', 'requests_count', 'leave_days_total', 'annual_leave_days_total'],
      filters: [],
      sort: { field: 'status', direction: 'asc' },
      limit: 50,
      name: 'Leaves by Status',
      descriptionText: 'Counts and leave-day totals per status',
    },
  },
  {
    key: 'employee_leave_summary',
    label: 'Employee Leave Summary',
    description: 'Joins employees + departments + leave requests (SQL aggregates)',
    payload: {
      source: 'employee_leave_summary',
      columns: [
        'employee_id',
        'employee_name',
        'department_name',
        'employee_status',
        'annual_leave_days',
        'annual_leave_used',
        'annual_leave_balance',
        'approved_leave_days_total',
        'approved_annual_leave_days',
        'pending_leave_days_total',
        'leave_requests_total',
      ],
      filters: [],
      sort: { field: 'department_name', direction: 'asc' },
      limit: 500,
      name: 'Employee Leave Summary',
      descriptionText: 'Joined leave totals + leave balances per employee',
    },
  },
  {
    key: 'payroll_department_summary',
    label: 'Payroll by Department',
    description: 'Join payroll + employees + departments with monthly aggregates',
    payload: {
      source: 'payroll_department_summary',
      columns: [
        'year',
        'month',
        'department_name',
        'employees_count',
        'gross_salary_total',
        'tax_total',
        'deductions_total',
        'net_salary_total',
      ],
      filters: [],
      sort: { field: 'year', direction: 'desc' },
      limit: 200,
      name: 'Payroll by Department',
      descriptionText: 'Department-level payroll totals by month/year',
    },
  },
  {
    key: 'attendance_department_summary',
    label: 'Attendance Department Summary',
    description: 'Join attendances + employees + departments with status breakdown',
    payload: {
      source: 'attendance_department_summary',
      columns: ['date', 'department_name', 'attendance_status', 'records_count', 'employees_count'],
      filters: [],
      sort: { field: 'date', direction: 'desc' },
      limit: 500,
      name: 'Attendance Department Summary',
      descriptionText: 'Daily attendance mix by department and status',
    },
  },
  {
    key: 'recruitment_funnel_summary',
    label: 'Recruitment Funnel Summary',
    description: 'Join ATS applications + jobs + departments with stage metrics',
    payload: {
      source: 'recruitment_funnel_summary',
      columns: ['stage', 'job_title', 'department_name', 'applications_count', 'candidates_count', 'average_rating'],
      filters: [],
      sort: { field: 'stage', direction: 'asc' },
      limit: 500,
      name: 'Recruitment Funnel Summary',
      descriptionText: 'Funnel counts and average rating by stage/job/department',
    },
  },
];

const applyPreset = (preset) => {
  form.value.source = preset.payload.source;
  form.value.columns = [...preset.payload.columns];
  form.value.filters = [...preset.payload.filters];
  form.value.sort = { ...preset.payload.sort };
  form.value.limit = preset.payload.limit;
  form.value.name = preset.payload.name;
  form.value.description = preset.payload.descriptionText;
};

const loadMetadata = async () => {
  const response = await apiClient.get('/api/reports/metadata');
  sources.value = response?.data?.data?.sources || [];
  if (!form.value.source && sources.value.length) {
    form.value.source = sources.value[0].key;
  }
};

const loadSavedReports = async () => {
  const response = await apiClient.get('/api/reports');
  savedReports.value = response?.data?.data || [];
};

const addFilter = () => {
  form.value.filters.push({ field: '', op: '=', value: '' });
};

const removeFilter = (index) => {
  form.value.filters.splice(index, 1);
};

const runReport = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const payload = {
      source: form.value.source,
      columns: form.value.columns,
      filters: form.value.filters.filter((filter) => filter.field && filter.op),
      sort: form.value.sort?.field ? form.value.sort : null,
      limit: form.value.limit || 500,
    };
    const response = await apiClient.post('/api/reports/run', payload);
    results.value = response?.data?.data || { columns: [], rows: [] };
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to run report.';
  } finally {
    isLoading.value = false;
  }
};

const saveReport = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const payload = {
      name: form.value.name,
      description: form.value.description,
      source: form.value.source,
      columns: form.value.columns,
      filters: form.value.filters.filter((filter) => filter.field && filter.op),
      sort: form.value.sort?.field ? form.value.sort : null,
      limit: form.value.limit || 500,
      is_shared: form.value.is_shared,
    };
    await apiClient.post('/api/reports', payload);
    await loadSavedReports();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save report.';
  } finally {
    isLoading.value = false;
  }
};

const runSaved = async (reportId) => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.post(`/api/reports/${reportId}/run`);
    results.value = response?.data?.data || { columns: [], rows: [] };
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to run saved report.';
  } finally {
    isLoading.value = false;
  }
};

const toggleColumn = (fieldKey) => {
  const columns = form.value.columns;
  if (columns.includes(fieldKey)) {
    form.value.columns = columns.filter((key) => key !== fieldKey);
  } else {
    form.value.columns = [...columns, fieldKey];
  }
};

const formatCell = (column, value) => formatByField(column, value);

onMounted(async () => {
  isLoading.value = true;
  try {
    await Promise.all([loadMetadata(), loadSavedReports()]);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load report data.';
  } finally {
    isLoading.value = false;
  }
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <header class="flex flex-col gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Reports</p>
        <h1 class="text-3xl font-semibold">Dynamic report builder</h1>
        <p class="max-w-2xl text-sm text-slate-300/70">
          Build custom reports by selecting a data source, fields, and filters. Save templates for reuse
          and track the KPIs that matter most.
        </p>
      </header>

      <section class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Key HR KPIs</p>
          <p class="mt-2 text-sm text-slate-300/80">
            Headcount, payroll cost, attendance rate, leave utilization, turnover, and performance trends.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Core HR functions</p>
          <p class="mt-2 text-sm text-slate-300/80">
            Recruitment, payroll, benefits, compliance, performance management, and employee relations.
          </p>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Strategic HR</p>
          <p class="mt-2 text-sm text-slate-300/80">
            Workforce planning, culture and engagement, change management, and HR analytics.
          </p>
        </div>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">KPI presets</p>
            <h2 class="mt-2 text-lg font-semibold">Quick-start report templates</h2>
          </div>
          <span class="text-xs text-slate-400">{{ presets.length }} presets</span>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <button
            v-for="preset in presets"
            :key="preset.key"
            class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-left text-sm transition hover:border-emerald-400/40 hover:bg-emerald-400/10"
            type="button"
            @click="applyPreset(preset)"
          >
            <p class="font-semibold text-slate-100">{{ preset.label }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ preset.description }}</p>
          </button>
        </div>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Configure report</h2>
            <span class="text-xs text-slate-400">{{ form.source || 'Choose source' }}</span>
          </div>

          <div class="mt-6 grid gap-4">
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Report name</span>
              <input
                v-model="form.name"
                type="text"
                class="mt-2 h-11 w-full rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm outline-none focus:border-emerald-300/60"
                placeholder="e.g. Payroll summary"
              />
            </label>
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Description</span>
              <input
                v-model="form.description"
                type="text"
                class="mt-2 h-11 w-full rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm outline-none focus:border-emerald-300/60"
                placeholder="Optional summary"
              />
            </label>
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Source</span>
              <select
                v-model="form.source"
                class="mt-2 h-11 w-full rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm outline-none focus:border-emerald-300/60"
              >
                <option v-for="source in sources" :key="source.key" :value="source.key">
                  {{ source.label }}
                </option>
              </select>
            </label>
          </div>

          <div class="mt-6">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Columns</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
              <label
                v-for="field in availableFields"
                :key="field.key"
                class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2 text-sm"
              >
                <input
                  type="checkbox"
                  class="h-4 w-4 accent-emerald-400"
                  :checked="form.columns.includes(field.key)"
                  @change="toggleColumn(field.key)"
                />
                <span>{{ field.label }}</span>
              </label>
            </div>
          </div>

          <div class="mt-6">
            <div class="flex items-center justify-between">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Filters</p>
              <button
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200"
                type="button"
                @click="addFilter"
              >
                Add filter
              </button>
            </div>
            <div v-if="!form.filters.length" class="mt-3 text-xs text-slate-400">
              No filters added.
            </div>
            <div v-for="(filter, index) in form.filters" :key="index" class="mt-3 grid gap-2 sm:grid-cols-[1fr_120px_1fr_auto]">
              <select
                v-model="filter.field"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="">Select field</option>
                <option v-for="field in availableFields" :key="field.key" :value="field.key">
                  {{ field.label }}
                </option>
              </select>
              <select
                v-model="filter.op"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="=">=</option>
                <option value="!=">!=</option>
                <option value=">">&gt;</option>
                <option value=">=">&gt;=</option>
                <option value="<">&lt;</option>
                <option value="<=">&lt;=</option>
                <option value="like">like</option>
                <option value="in">in</option>
                <option value="between">between</option>
              </select>
              <input
                v-model="filter.value"
                type="text"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
                placeholder="Value"
              />
              <button
                class="rounded-xl border border-rose-500/30 px-3 text-xs text-rose-200"
                type="button"
                @click="removeFilter(index)"
              >
                Remove
              </button>
            </div>
          </div>

          <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Sort field</span>
              <select
                v-model="form.sort.field"
                class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="">None</option>
                <option v-for="field in availableFields" :key="field.key" :value="field.key">
                  {{ field.label }}
                </option>
              </select>
            </label>
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Direction</span>
              <select
                v-model="form.sort.direction"
                class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
              </select>
            </label>
            <label class="text-sm">
              <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Limit</span>
              <input
                v-model.number="form.limit"
                type="number"
                min="1"
                max="5000"
                class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              />
            </label>
            <label class="flex items-center gap-3 text-sm">
              <input v-model="form.is_shared" type="checkbox" class="h-4 w-4 accent-emerald-400" />
              <span>Share with company admins</span>
            </label>
          </div>

          <div class="mt-6 flex flex-wrap gap-3">
            <button
              class="rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950"
              type="button"
              :disabled="isLoading"
              @click="runReport"
            >
              Run report
            </button>
            <button
              class="rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200"
              type="button"
              :disabled="isLoading"
              @click="saveReport"
            >
              Save report
            </button>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Saved reports</h2>
          <div class="mt-4 space-y-3">
            <div
              v-for="report in savedReports"
              :key="report.id"
              class="rounded-2xl border border-white/10 bg-slate-950/40 p-4"
            >
              <p class="text-sm font-semibold">{{ report.name }}</p>
              <p class="text-xs text-slate-400">{{ report.description || report.source }}</p>
              <button
                class="mt-3 rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200"
                type="button"
                :disabled="isLoading"
                @click="runSaved(report.id)"
              >
                Run
              </button>
            </div>
            <p v-if="!savedReports.length" class="text-xs text-slate-400">
              No saved reports yet.
            </p>
          </div>
        </div>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Report output</h2>
          <span class="text-xs text-slate-400">{{ results.rows?.length || 0 }} rows</span>
        </div>

        <div v-if="results.columns?.length" class="mt-4 overflow-x-auto">
          <table class="min-w-[760px] text-left text-sm text-slate-200">
            <thead class="text-xs uppercase text-slate-400">
              <tr>
                <th v-for="column in results.columns" :key="column" class="px-3 py-2">
                  {{ column }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, rowIndex) in results.rows" :key="rowIndex" class="border-t border-white/5">
                <td v-for="column in results.columns" :key="column" class="px-3 py-2">
                  {{ formatCell(column, row[column]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p v-else class="mt-4 text-sm text-slate-400">
          Run a report to see results.
        </p>
      </section>
    </div>
  </div>
</template>



