<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import ApexCharts from 'vue3-apexcharts';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'DashboardsPage' });

const dashboards = ref([]);
const activeDashboardId = ref(null);
const activeDashboard = ref(null);
const widgets = ref([]);
const sources = ref([]);
const errorMessage = ref('');
const isLoading = ref(false);
const isCreatingPreset = ref(false);
const route = useRoute();
const router = useRouter();

const { hasRole } = useAuth();
const canManageDashboards = computed(() => hasRole(['admin', 'hr', 'finance', 'director', 'md']));
const isDetailsOnlyView = computed(() => Boolean(route.params.dashboardId));

const dashboardForm = ref({
  name: '',
  description: '',
});

const widgetForm = ref({
  title: '',
  source: '',
  chart_type: 'table',
  columns: [],
  filters: [],
  sort: { field: '', direction: 'desc' },
  limit: 100,
  x_field: '',
  y_field: '',
  aggregate: 'count',
  width: 6,
  height: 4,
});

const activeSource = computed(() => sources.value.find((item) => item.key === widgetForm.value.source));
const availableFields = computed(() => activeSource.value?.fields || []);

const loadMetadata = async () => {
  const response = await apiClient.get('/api/reports/metadata');
  sources.value = response?.data?.data?.sources || [];
  if (!widgetForm.value.source && sources.value.length) {
    widgetForm.value.source = sources.value[0].key;
  }
};

const loadDashboards = async () => {
  const response = await apiClient.get('/api/dashboards');
  dashboards.value = response?.data?.data || [];

  const routeDashboardId = route.params.dashboardId ? Number(route.params.dashboardId) : null;
  if (routeDashboardId && dashboards.value.some((item) => Number(item.id) === routeDashboardId)) {
    activeDashboardId.value = routeDashboardId;
    return;
  }

  if (!activeDashboardId.value && dashboards.value.length) {
    activeDashboardId.value = dashboards.value[0].id;
  }
};

const loadDashboard = async () => {
  if (!activeDashboardId.value) return;
  const response = await apiClient.post(`/api/dashboards/${activeDashboardId.value}/run`);
  activeDashboard.value = response?.data?.data?.dashboard || null;
  widgets.value = response?.data?.data?.widgets || [];
};

const createDashboard = async () => {
  if (!dashboardForm.value.name) return;
  const response = await apiClient.post('/api/dashboards', dashboardForm.value);
  const createdId = response?.data?.data?.id ?? null;
  dashboardForm.value = { name: '', description: '' };
  await loadDashboards();
  if (createdId) {
    activeDashboardId.value = createdId;
  }
  await loadDashboard();
  window.dispatchEvent(new CustomEvent('vegro:dashboards:updated'));
};
const executivePresetWidgets = [
  {
    title: 'Headcount by Status',
    source: 'employees',
    chart_type: 'donut',
    x_field: 'status',
    aggregate: 'count',
    limit: 50,
  },
  {
    title: 'Employees by Department',
    source: 'employees',
    chart_type: 'bar',
    x_field: 'department_id',
    aggregate: 'count',
    limit: 50,
  },
  {
    title: 'Payroll by Month',
    source: 'payrolls',
    chart_type: 'bar',
    x_field: 'month',
    y_field: 'net_salary',
    aggregate: 'sum',
    limit: 12,
  },
  {
    title: 'Leave Requests by Status',
    source: 'leave_requests',
    chart_type: 'donut',
    x_field: 'status',
    aggregate: 'count',
    limit: 10,
  },
  {
    title: 'Attendance Status',
    source: 'attendances',
    chart_type: 'donut',
    x_field: 'status',
    aggregate: 'count',
    limit: 10,
  },
];

const createExecutiveDashboard = async () => {
  if (!canManageDashboards.value) return;
  if (isCreatingPreset.value) return;
  isCreatingPreset.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.post('/api/dashboards', {
      name: 'Executive KPI Dashboard',
      description: 'Headcount, payroll, leave, and attendance overview',
    });
    const dashboardId = response?.data?.data?.id;
    if (!dashboardId) {
      throw new Error('Dashboard creation failed');
    }
    for (const widget of executivePresetWidgets) {
      await apiClient.post(`/api/dashboards/${dashboardId}/widgets`, {
        title: widget.title,
        source: widget.source,
        chart_type: widget.chart_type,
        x_field: widget.x_field,
        y_field: widget.y_field,
        aggregate: widget.aggregate,
        limit: widget.limit,
      });
    }
    await loadDashboards();
    activeDashboardId.value = dashboardId;
    await loadDashboard();
    window.dispatchEvent(new CustomEvent('vegro:dashboards:updated'));
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Unable to create executive dashboard preset.';
  } finally {
    isCreatingPreset.value = false;
  }
};

const addFilter = () => {
  widgetForm.value.filters.push({ field: '', op: '=', value: '' });
};

const removeFilter = (index) => {
  widgetForm.value.filters.splice(index, 1);
};

const toggleColumn = (fieldKey) => {
  const columns = widgetForm.value.columns;
  if (columns.includes(fieldKey)) {
    widgetForm.value.columns = columns.filter((key) => key !== fieldKey);
  } else {
    widgetForm.value.columns = [...columns, fieldKey];
  }
};

const addWidget = async () => {
  if (!activeDashboardId.value) return;
  const payload = {
    ...widgetForm.value,
    filters: widgetForm.value.filters.filter((filter) => filter.field && filter.op),
    sort: widgetForm.value.sort?.field ? widgetForm.value.sort : null,
  };
  await apiClient.post(`/api/dashboards/${activeDashboardId.value}/widgets`, payload);
  widgetForm.value.title = '';
  widgetForm.value.filters = [];
  widgetForm.value.columns = [];
  await loadDashboard();
};
const chartOptions = (widget) => ({
  chart: {
    type: widget.chart_type,
    toolbar: { show: false },
    foreColor: '#cbd5f5',
  },
  labels: widget.data?.labels || [],
  xaxis: {
    categories: widget.data?.labels || [],
    labels: { style: { colors: '#94a3b8' } },
  },
  yaxis: {
    labels: { style: { colors: '#94a3b8' } },
  },
  dataLabels: { enabled: false },
  colors: ['#34d399', '#38bdf8', '#f59e0b', '#f87171'],
  legend: { labels: { colors: '#94a3b8' } },
  grid: { borderColor: 'rgba(148,163,184,0.15)' },
});

const chartSeries = (widget) => {
  const data = widget.data?.series || [];
  if (widget.chart_type === 'donut') {
    return data;
  }
  return [{ name: widget.title, data }];
};

onMounted(async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    await Promise.all([loadMetadata(), loadDashboards()]);
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load dashboards.';
  } finally {
    isLoading.value = false;
  }
});
watch(
  () => route.params.dashboardId,
  async (value) => {
    if (!value) return;
    const dashboardId = Number(value);
    if (!Number.isFinite(dashboardId)) return;
    if (dashboardId === Number(activeDashboardId.value)) return;
    activeDashboardId.value = dashboardId;
    await loadDashboard();
  },
);

watch(activeDashboardId, (value) => {
  if (!value) return;
  if (!isDetailsOnlyView.value) return;
  const current = route.params.dashboardId ? Number(route.params.dashboardId) : null;
  if (current === Number(value)) return;
  router.replace({ name: 'DashboardView', params: { dashboardId: String(value) } });
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <header class="flex flex-col gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Dashboards</p>
        <h1 class="text-3xl font-semibold">{{ isDetailsOnlyView ? (activeDashboard?.name || "Dashboard details") : "User-defined chartboards" }}</h1>
        <p class="max-w-2xl text-sm text-slate-300/70">
          {{ isDetailsOnlyView ? "Viewing a saved dashboard in details mode." : "Build custom dashboards with tables and charts. Each board is private to your user." }}
        </p>
      
      </header>

      <section v-if="!isDetailsOnlyView" class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Executive preset</p>
            <h2 class="mt-2 text-lg font-semibold">Director and MD KPI dashboard</h2>
            <p class="mt-2 text-sm text-slate-300/70">
              One-click setup for headcount, payroll, leave, and attendance visibility.
            </p>
          </div>
          <button
            class="rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950 disabled:cursor-not-allowed disabled:opacity-60"
            type="button"
            :disabled="!canManageDashboards || isCreatingPreset"
            @click="createExecutiveDashboard"
          >
            {{ isCreatingPreset ? 'Creating...' : 'Create preset dashboard' }}
          </button>
        </div>
        <p v-if="!canManageDashboards" class="mt-3 text-xs text-slate-400">
          Only admin, HR, or finance can create dashboards. Directors and MDs can view assigned boards.
        </p>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section v-if="!isDetailsOnlyView" class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Your dashboards</h2>
          <div class="mt-4 space-y-3">
            <button
              v-for="dashboard in dashboards"
              :key="dashboard.id"
              class="w-full rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-left text-sm transition"
              :class="dashboard.id === activeDashboardId ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200' : 'hover:border-white/20'"
              type="button"
              @click="activeDashboardId = dashboard.id; loadDashboard()"
            >
              <p class="font-semibold">{{ dashboard.name }}</p>
              <p class="text-xs text-slate-400">{{ dashboard.description || 'No description' }}</p>
            </button>
            <p v-if="!dashboards.length" class="text-xs text-slate-400">
              No dashboards yet. Create one below.
            </p>
          </div>

          <div class="mt-6 border-t border-white/10 pt-4">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Create dashboard</p>
            <input
              v-model="dashboardForm.name"
              class="mt-3 h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Dashboard name"
            />
            <input
              v-model="dashboardForm.description"
              class="mt-3 h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Description (optional)"
            />
            <button
              class="mt-4 rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950"
              type="button"
              :disabled="!canManageDashboards"
              @click="createDashboard"
            >
              Create
            </button>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Add widget</h2>
          <div class="mt-4 grid gap-4">
            <input
              v-model="widgetForm.title"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Widget title"
            />
            <div class="grid gap-3 sm:grid-cols-2">
              <select
                v-model="widgetForm.source"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option v-for="source in sources" :key="source.key" :value="source.key">
                  {{ source.label }}
                </option>
              </select>
              <select
                v-model="widgetForm.chart_type"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="table">Table</option>
                <option value="bar">Bar</option>
                <option value="line">Line</option>
                <option value="area">Area</option>
                <option value="donut">Donut</option>
              </select>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
              <select
                v-model="widgetForm.x_field"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="">X field</option>
                <option v-for="field in availableFields" :key="field.key" :value="field.key">
                  {{ field.label }}
                </option>
              </select>
              <select
                v-model="widgetForm.y_field"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="">Y field</option>
                <option v-for="field in availableFields" :key="field.key" :value="field.key">
                  {{ field.label }}
                </option>
              </select>
              <select
                v-model="widgetForm.aggregate"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              >
                <option value="count">Count</option>
                <option value="sum">Sum</option>
                <option value="avg">Average</option>
                <option value="min">Minimum</option>
                <option value="max">Maximum</option>
              </select>
            </div>

            <div>
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Columns (table)</p>
              <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <label
                  v-for="field in availableFields"
                  :key="field.key"
                  class="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2 text-sm"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 accent-emerald-400"
                    :checked="widgetForm.columns.includes(field.key)"
                    @change="toggleColumn(field.key)"
                  />
                  <span>{{ field.label }}</span>
                </label>
              </div>
            </div>

            <div>
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
              <div v-for="(filter, index) in widgetForm.filters" :key="index" class="mt-3 grid gap-2 sm:grid-cols-[1fr_120px_1fr_auto]">
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

            <div class="grid gap-3 sm:grid-cols-3">
              <input
                v-model.number="widgetForm.limit"
                type="number"
                min="1"
                max="5000"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
                placeholder="Limit"
              />
              <input
                v-model.number="widgetForm.width"
                type="number"
                min="3"
                max="12"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
                placeholder="Width"
              />
              <input
                v-model.number="widgetForm.height"
                type="number"
                min="2"
                max="12"
                class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
                placeholder="Height"
              />
            </div>
          </div>

          <button
            class="mt-6 rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950"
            type="button"
            :disabled="!canManageDashboards"
            @click="addWidget"
          >
            Add widget
          </button>
        </div>
      </section>

      

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">{{ isDetailsOnlyView ? "Dashboard details" : "Dashboard view" }}</h2>
          <span class="text-xs text-slate-400">{{ widgets.length }} widgets</span>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <div
            v-for="widget in widgets"
            :key="widget.id"
            class="rounded-2xl border border-white/10 bg-slate-950/40 p-4"
          >
            <p class="text-sm font-semibold">{{ widget.title }}</p>
            <p class="text-xs text-slate-400">{{ widget.source }} - {{ widget.chart_type }}</p>

            <div v-if="widget.chart_type === 'table'" class="mt-4 overflow-x-auto">
              <table class="min-w-[760px] text-left text-xs text-slate-200">
                <thead class="text-[11px] uppercase text-slate-400">
                  <tr>
                    <th v-for="column in widget.data?.columns || []" :key="column" class="px-2 py-2">
                      {{ column }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, rowIndex) in widget.data?.rows || []" :key="rowIndex" class="border-t border-white/5">
                    <td v-for="column in widget.data?.columns || []" :key="column" class="px-2 py-2">
                      {{ row[column] }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="mt-4">
              <ApexCharts
                :type="widget.chart_type"
                height="240"
                :options="chartOptions(widget)"
                :series="chartSeries(widget)"
              />
            </div>
          </div>
        </div>
        <p v-if="!widgets.length" class="mt-4 text-sm text-slate-400">
          {{ isDetailsOnlyView ? "No widgets found for this dashboard." : "Add widgets to see your dashboard here." }}
        </p>
      </section>
    </div>
  </div>
</template>



















