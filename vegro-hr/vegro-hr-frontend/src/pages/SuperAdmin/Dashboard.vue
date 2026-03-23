<script setup>
import { computed, onMounted, ref } from 'vue';
import ApexCharts from 'vue3-apexcharts';
import apiClient from '../../api/apiClient';
import { formatDate } from '../../utils/dateFormat';

defineOptions({ name: 'SuperAdminDashboardPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const stats = ref({
  companies: 0,
  users: 0,
  environments: {},
  status: {},
  plans: {},
});
const recentCompanies = ref([]);
const recentUsers = ref([]);
const topCompanies = ref([]);
const companies = ref([]);
const plans = ref([]);
const subscriptions = ref([]);
const companyEdits = ref({});

const onboardingForm = ref({
  name: '',
  domain: '',
  industry: '',
  country: '',
  plan_id: '',
  status: 'active',
  environment: 'demo',
  seed_demo: true,
  admin_name: '',
  admin_email: '',
  admin_password: '',
});

const planForm = ref({
  name: '',
  description: '',
  price: '',
  currency: 'USD',
});

const domainInputs = ref({});
const planSelections = ref({});

const formatCount = (value) => Number(value || 0).toLocaleString();


const chartOptions = (labels) => ({
  chart: { type: 'donut', foreColor: '#cbd5f5' },
  labels,
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  dataLabels: { enabled: false },
  colors: ['#34d399', '#38bdf8', '#f59e0b', '#f87171', '#a78bfa', '#f472b6'],
});

const environmentSeries = computed(() => Object.values(stats.value.environments || {}).map(Number));
const environmentLabels = computed(() => Object.keys(stats.value.environments || {}));

const statusSeries = computed(() => Object.values(stats.value.status || {}).map(Number));
const statusLabels = computed(() => Object.keys(stats.value.status || {}));

const planSeries = computed(() => Object.values(stats.value.plans || {}).map(Number));
const planLabels = computed(() => Object.keys(stats.value.plans || {}));
const activeSubscriptions = computed(() => subscriptions.value.filter((sub) => sub.status === 'active').length);

const loadDashboard = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const [
      dashboardResponse,
      companiesResponse,
      plansResponse,
      subscriptionsResponse,
    ] = await Promise.all([
      apiClient.get('/api/super/dashboard'),
      apiClient.get('/api/companies'),
      apiClient.get('/api/plans'),
      apiClient.get('/api/subscriptions'),
    ]);

    const data = dashboardResponse?.data?.data || {};
    stats.value = data.stats || stats.value;
    recentCompanies.value = data.recent_companies || [];
    recentUsers.value = data.recent_users || [];
    topCompanies.value = data.top_companies_by_users || [];

    companies.value = companiesResponse?.data?.data || [];
    plans.value = plansResponse?.data?.data || [];
    subscriptions.value = subscriptionsResponse?.data?.data || [];

    const edits = {};
    companies.value.forEach((company) => {
      edits[company.id] = {
        name: company.name || '',
        industry: company.industry || '',
        country: company.country || '',
        environment: company.environment || 'demo',
      };
    });
    companyEdits.value = edits;
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || 'Unable to load super admin dashboard.';
  } finally {
    isLoading.value = false;
  }
};

const submitCompany = async () => {
  errorMessage.value = '';
  try {
    await apiClient.post('/api/companies', {
      ...onboardingForm.value,
      plan_id: onboardingForm.value.plan_id || null,
    });
    onboardingForm.value = {
      name: '',
      domain: '',
      industry: '',
      country: '',
      plan_id: '',
      status: 'active',
      environment: 'demo',
      seed_demo: true,
      admin_name: '',
      admin_email: '',
      admin_password: '',
    };
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to onboard company.';
  }
};

const toggleCompanyStatus = async (company) => {
  if (!company) return;
  errorMessage.value = '';
  try {
    if (company.status === 'active') {
      await apiClient.post(`/api/companies/${company.id}/suspend`);
    } else {
      await apiClient.post(`/api/companies/${company.id}/resume`);
    }
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update company status.';
  }
};

const addDomain = async (companyId) => {
  const domain = domainInputs.value[companyId];
  if (!domain) return;
  errorMessage.value = '';
  try {
    await apiClient.post(`/api/companies/${companyId}/domains`, { domain, is_primary: true });
    domainInputs.value[companyId] = '';
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to add domain.';
  }
};

const updatePlan = async (companyId) => {
  const planId = planSelections.value[companyId];
  if (!planId) return;
  errorMessage.value = '';
  try {
    await apiClient.post(`/api/companies/${companyId}/plan`, { plan_id: Number(planId) });
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update plan.';
  }
};

const updateCompanyProfile = async (companyId) => {
  const payload = companyEdits.value[companyId];
  if (!payload) return;
  errorMessage.value = '';
  try {
    await apiClient.put(`/api/companies/${companyId}`, payload);
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update company.';
  }
};

const createPlan = async () => {
  if (!planForm.value.name) return;
  errorMessage.value = '';
  try {
    await apiClient.post('/api/plans', {
      name: planForm.value.name,
      description: planForm.value.description || null,
      price: planForm.value.price ? Number(planForm.value.price) : null,
      currency: planForm.value.currency || 'USD',
    });
    planForm.value = { name: '', description: '', price: '', currency: 'USD' };
    await loadDashboard();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to create plan.';
  }
};

onMounted(loadDashboard);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
      <div class="flex flex-col gap-10">
        <section id="overview" class="flex flex-col gap-4">
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            Super Admin
          </p>
          <h1 class="text-3xl font-semibold leading-tight sm:text-4xl">
            Global control center
          </h1>
          <p class="max-w-2xl text-sm text-slate-200/70 sm:text-base">
            Manage company onboarding, environments, plans, and access to the system.
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
            <RouterLink
              class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-emerald-200"
              to="/dashboard/super/companies"
            >
              Manage companies
            </RouterLink>
            <RouterLink
              class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-slate-200"
              to="/dashboard/super/billing"
            >
              View billing
            </RouterLink>
          </div>
        </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Companies</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(stats.companies) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Users</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(stats.users) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Plans</p>
          <p class="mt-3 text-3xl font-semibold">{{ planLabels.length }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Active licenses</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(activeSubscriptions) }}</p>
        </article>
      </section>

      <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Environments</p>
          <h2 class="mt-2 text-lg font-semibold">Company mix</h2>
          <div class="mt-4">
            <ApexCharts
              v-if="environmentSeries.length"
              type="donut"
              height="240"
              :options="chartOptions(environmentLabels)"
              :series="environmentSeries"
            />
            <p v-else class="text-sm text-slate-400">No environment data.</p>
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Status</p>
          <h2 class="mt-2 text-lg font-semibold">Active vs inactive</h2>
          <div class="mt-4">
            <ApexCharts
              v-if="statusSeries.length"
              type="donut"
              height="240"
              :options="chartOptions(statusLabels)"
              :series="statusSeries"
            />
            <p v-else class="text-sm text-slate-400">No status data.</p>
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Plans</p>
          <h2 class="mt-2 text-lg font-semibold">Plan adoption</h2>
          <div class="mt-4">
            <ApexCharts
              v-if="planSeries.length"
              type="donut"
              height="240"
              :options="chartOptions(planLabels)"
              :series="planSeries"
            />
            <p v-else class="text-sm text-slate-400">No plan data.</p>
          </div>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Recent Companies</p>
          <h2 class="mt-2 text-lg font-semibold">Latest onboarded</h2>
          <div class="mt-4 overflow-x-auto">
            <table class="min-w-[760px] text-left text-xs text-slate-200">
              <thead class="text-[11px] uppercase text-slate-400">
                <tr>
                  <th class="px-3 py-2">Name</th>
                  <th class="px-3 py-2">Environment</th>
                  <th class="px-3 py-2">Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="company in recentCompanies" :key="company.id" class="border-t border-white/5">
                  <td class="px-3 py-2">{{ company.name }}</td>
                  <td class="px-3 py-2">{{ company.environment }}</td>
                  <td class="px-3 py-2">{{ company.status }}</td>
                </tr>
                <tr v-if="!recentCompanies.length">
                  <td class="px-3 py-4 text-sm text-slate-400" colspan="3">
                    No companies found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">System access</p>
          <h2 class="mt-2 text-lg font-semibold">Company admin scope</h2>
          <div class="mt-4 space-y-3 text-sm text-slate-300/80">
            <p>
              Company admins manage RBAC roles and permissions inside their tenant.
              Super Admin grants access, environments, and licensing for each company.
            </p>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Next step</p>
              <p class="mt-2 text-sm">Open Roles and System Users pages for tenant-level access controls.</p>
            </div>
          </div>
        </div>
      </section>

      <section id="onboarding" class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Onboard company</p>
          <h2 class="mt-2 text-lg font-semibold">Create a new tenant</h2>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <input v-model="onboardingForm.name" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Company name" />
            <input v-model="onboardingForm.domain" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Primary domain" />
            <input v-model="onboardingForm.industry" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Industry" />
            <input v-model="onboardingForm.country" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Country code" />
            <select v-model="onboardingForm.plan_id" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none">
              <option value="">Select plan</option>
              <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
            </select>
            <select v-model="onboardingForm.environment" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none">
              <option value="demo">Demo</option>
              <option value="staging">Staging</option>
              <option value="production">Production</option>
            </select>
            <input v-model="onboardingForm.admin_name" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Admin name" />
            <input v-model="onboardingForm.admin_email" type="email" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Admin email" />
            <input v-model="onboardingForm.admin_password" type="password" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Admin password" />
            <select v-model="onboardingForm.status" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <label class="flex items-center gap-2 text-xs text-slate-300">
              <input v-model="onboardingForm.seed_demo" type="checkbox" class="h-4 w-4 accent-emerald-400" />
              Seed demo data
            </label>
          </div>
          <button
            class="mt-4 rounded-full bg-emerald-400 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-950"
            type="button"
            @click="submitCompany"
          >
            Onboard company
          </button>
        </div>

        <div id="plans" class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Plans & licenses</p>
          <h2 class="mt-2 text-lg font-semibold">Manage plans</h2>
          <div class="mt-4 space-y-3">
            <div v-for="plan in plans" :key="plan.id" class="rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm">
              <p class="font-semibold">{{ plan.name }}</p>
              <p class="text-xs text-slate-400">{{ plan.description || 'No description' }}</p>
            </div>
            <p v-if="!plans.length" class="text-xs text-slate-400">No plans yet.</p>
          </div>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <input v-model="planForm.name" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Plan name" />
            <input v-model="planForm.price" type="number" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Price" />
            <input v-model="planForm.currency" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Currency" />
            <input v-model="planForm.description" class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none" placeholder="Description" />
          </div>
          <button
            class="mt-4 rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200"
            type="button"
            @click="createPlan"
          >
            Create plan
          </button>
        </div>
      </section>

      <section id="companies" class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Companies</p>
        <h2 class="mt-2 text-lg font-semibold">Manage tenants</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-[760px] text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Company</th>
                <th class="px-3 py-2">Environment</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Plan</th>
                <th class="px-3 py-2">Domains</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="company in companies" :key="company.id" class="border-t border-white/5">
                <td class="px-3 py-2">
                  <input
                    v-model="companyEdits[company.id].name"
                    class="h-8 w-40 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-[11px] outline-none"
                  />
                  <p class="text-[11px] text-slate-400 mt-1">ID: {{ company.id }}</p>
                </td>
                <td class="px-3 py-2">
                  <select
                    v-model="companyEdits[company.id].environment"
                    class="h-8 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-[11px] outline-none"
                  >
                    <option value="demo">demo</option>
                    <option value="staging">staging</option>
                    <option value="production">production</option>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <span
                    class="rounded-full px-2 py-1 text-[10px]"
                    :class="company.status === 'active'
                      ? 'bg-emerald-400/10 text-emerald-200'
                      : 'bg-rose-400/10 text-rose-200'"
                  >
                    {{ company.status }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <input
                    v-model="companyEdits[company.id].industry"
                    class="h-8 w-32 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-[11px] outline-none"
                    placeholder="Industry"
                  />
                  <input
                    v-model="companyEdits[company.id].country"
                    class="mt-2 h-8 w-24 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-[11px] outline-none"
                    placeholder="Country"
                  />
                </td>
                <td class="px-3 py-2">
                  <select
                    v-model="planSelections[company.id]"
                    class="h-9 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-xs outline-none"
                  >
                    <option value="">Select</option>
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                      {{ plan.name }}
                    </option>
                  </select>
                  <button
                    class="mt-2 w-full rounded-full border border-white/10 px-2 py-1 text-[10px] text-slate-200"
                    type="button"
                    @click="updatePlan(company.id)"
                  >
                    Update plan
                  </button>
                </td>
                <td class="px-3 py-2">
                  <div class="space-y-1">
                    <p v-for="domain in company.domains || []" :key="domain.id" class="text-[11px]">
                      {{ domain.domain }}<span v-if="domain.is_primary" class="text-emerald-200"> - primary</span>
                    </p>
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <input
                      v-model="domainInputs[company.id]"
                      class="h-8 w-28 rounded-xl border border-white/10 bg-slate-950/40 px-2 text-[10px] outline-none"
                      placeholder="add domain"
                    />
                    <button
                      class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-2 py-1 text-[10px] text-emerald-200"
                      type="button"
                      @click="addDomain(company.id)"
                    >
                      Add
                    </button>
                  </div>
                </td>
                <td class="px-3 py-2">
                  <button
                    class="rounded-full border border-white/10 px-3 py-1 text-[10px] text-slate-200"
                    type="button"
                    @click="toggleCompanyStatus(company)"
                  >
                    {{ company.status === 'active' ? 'Suspend' : 'Resume' }}
                  </button>
                  <button
                    class="mt-2 rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-[10px] text-emerald-200"
                    type="button"
                    @click="updateCompanyProfile(company.id)"
                  >
                    Save profile
                  </button>
                </td>
              </tr>
              <tr v-if="!companies.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="6">
                  No companies found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section id="licenses" class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Subscriptions</p>
        <h2 class="mt-2 text-lg font-semibold">Active licenses</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-[760px] text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Company</th>
                <th class="px-3 py-2">Plan</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Starts</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="subscription in subscriptions" :key="subscription.id" class="border-t border-white/5">
                <td class="px-3 py-2">{{ subscription.company?.name || subscription.company_id }}</td>
                <td class="px-3 py-2">{{ subscription.plan?.name || subscription.plan_id }}</td>
                <td class="px-3 py-2">{{ subscription.status }}</td>
                <td class="px-3 py-2">{{ formatDate(subscription.starts_at) }}</td>
              </tr>
              <tr v-if="!subscriptions.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="4">
                  No subscriptions found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Top Companies</p>
        <h2 class="mt-2 text-lg font-semibold">Users by company</h2>
        <div class="mt-4 overflow-x-auto">
          <table class="min-w-[760px] text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Company</th>
                <th class="px-3 py-2">Users</th>
                <th class="px-3 py-2">Environment</th>
                <th class="px-3 py-2">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="company in topCompanies" :key="company.id" class="border-t border-white/5">
                <td class="px-3 py-2">{{ company.name }}</td>
                <td class="px-3 py-2">{{ company.users_count }}</td>
                <td class="px-3 py-2">{{ company.environment }}</td>
                <td class="px-3 py-2">{{ company.status }}</td>
              </tr>
              <tr v-if="!topCompanies.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="4">
                  No data available.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      </div>
    </div>
  </div>
</template>



