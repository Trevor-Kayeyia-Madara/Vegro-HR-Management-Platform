<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

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

const unwrap = (result) => {
  if (!result || result.status !== 'fulfilled') return null;
  const payload = result.value?.data;
  return payload?.data ?? payload ?? null;
};

const listFrom = (value) => {
  if (Array.isArray(value)) return value;
  if (Array.isArray(value?.data)) return value.data;
  return [];
};

const mapToEntries = (value) => {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return [];
  return Object.entries(value)
    .map(([label, total]) => ({ label, total: Number(total) || 0 }))
    .sort((a, b) => b.total - a.total);
};

const loadDashboard = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  const results = await Promise.allSettled([
    apiClient.get('/api/super/dashboard'),
    apiClient.get('/api/companies'),
    apiClient.get('/api/plans'),
    apiClient.get('/api/subscriptions'),
  ]);

  const dashboard = unwrap(results[0]);
  if (dashboard && typeof dashboard === 'object') {
    stats.value = { ...stats.value, ...(dashboard.stats || {}) };
    recentCompanies.value = listFrom(dashboard.recent_companies);
    recentUsers.value = listFrom(dashboard.recent_users);
    topCompanies.value = listFrom(dashboard.top_companies_by_users);
  }

  companies.value = listFrom(unwrap(results[1]));
  plans.value = listFrom(unwrap(results[2]));
  subscriptions.value = listFrom(unwrap(results[3]));

  const failed = results.find((result) => result.status === 'rejected');
  if (failed) {
    errorMessage.value =
      failed.reason?.response?.data?.message ||
      'Some dashboard data could not be loaded. Please refresh and try again.';
  }

  isLoading.value = false;
};

const environments = computed(() => mapToEntries(stats.value.environments));
const statuses = computed(() => mapToEntries(stats.value.status));
const planMix = computed(() => mapToEntries(stats.value.plans));
const activeSubscriptions = computed(
  () => subscriptions.value.filter((subscription) => subscription?.status === 'active').length,
);
const maxEnvironment = computed(() => Math.max(1, ...environments.value.map((item) => item.total)));
const maxStatus = computed(() => Math.max(1, ...statuses.value.map((item) => item.total)));
const maxPlan = computed(() => Math.max(1, ...planMix.value.map((item) => item.total)));

const number = (value) => Number(value || 0).toLocaleString();
const date = (value) => {
  if (!value) return '—';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime())
    ? '—'
    : parsed.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
};

const barWidth = (total, maximum) => `${Math.max(4, Math.round((total / maximum) * 100))}%`;

onMounted(loadDashboard);
</script>

<template>
  <main class="min-h-full bg-[#07111f] text-slate-100">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
      <header class="relative overflow-hidden rounded-[2rem] border border-cyan-300/10 bg-gradient-to-br from-cyan-400/15 via-slate-900 to-indigo-500/10 p-6 shadow-2xl shadow-cyan-950/20 sm:p-9">
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl" />
        <div class="relative flex flex-col justify-between gap-6 md:flex-row md:items-end">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-200">Platform command center</p>
            <h1 class="mt-3 max-w-2xl text-3xl font-semibold tracking-tight sm:text-5xl">Know what is happening across every company.</h1>
            <p class="mt-4 max-w-xl text-sm leading-6 text-slate-300">A clear view of tenants, access, plans, and platform health for the Super Admin team.</p>
          </div>
          <button class="rounded-full border border-cyan-200/20 bg-cyan-200/10 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-100 transition hover:bg-cyan-200/20" type="button" @click="loadDashboard">
            {{ isLoading ? 'Refreshing…' : 'Refresh data' }}
          </button>
        </div>
      </header>

      <p v-if="errorMessage" class="rounded-2xl border border-amber-300/20 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">{{ errorMessage }}</p>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in [
          { label: 'Companies', value: stats.companies, hint: 'Tenants on the platform', color: 'text-cyan-200' },
          { label: 'Users', value: stats.users, hint: 'Across all tenants', color: 'text-violet-200' },
          { label: 'Plans', value: planMix.length, hint: 'Current plan mix', color: 'text-emerald-200' },
          { label: 'Active licenses', value: activeSubscriptions, hint: 'Active subscriptions', color: 'text-amber-200' },
        ]" :key="card.label" class="rounded-3xl border border-white/10 bg-white/[0.04] p-5 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/[0.07]">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
            <span :class="card.color" class="text-lg">●</span>
          </div>
          <p class="mt-4 text-4xl font-semibold tracking-tight">{{ number(card.value) }}</p>
          <p class="mt-2 text-xs text-slate-400">{{ card.hint }}</p>
        </article>
      </section>

      <section class="grid gap-5 lg:grid-cols-3">
        <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Environment</p>
          <h2 class="mt-2 text-lg font-semibold">Where companies run</h2>
          <div class="mt-6 space-y-4">
            <div v-for="item in environments" :key="item.label">
              <div class="mb-2 flex justify-between text-xs"><span class="capitalize text-slate-300">{{ item.label }}</span><span class="text-slate-500">{{ number(item.total) }}</span></div>
              <div class="h-2 rounded-full bg-slate-800"><div class="h-2 rounded-full bg-cyan-300" :style="{ width: barWidth(item.total, maxEnvironment) }" /></div>
            </div>
            <p v-if="!environments.length" class="text-sm text-slate-500">No environment data yet.</p>
          </div>
        </article>

        <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
          <h2 class="mt-2 text-lg font-semibold">Platform availability</h2>
          <div class="mt-6 space-y-4">
            <div v-for="item in statuses" :key="item.label">
              <div class="mb-2 flex justify-between text-xs"><span class="capitalize text-slate-300">{{ item.label }}</span><span class="text-slate-500">{{ number(item.total) }}</span></div>
              <div class="h-2 rounded-full bg-slate-800"><div class="h-2 rounded-full bg-emerald-300" :style="{ width: barWidth(item.total, maxStatus) }" /></div>
            </div>
            <p v-if="!statuses.length" class="text-sm text-slate-500">No status data yet.</p>
          </div>
        </article>

        <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Plans</p>
          <h2 class="mt-2 text-lg font-semibold">Plan adoption</h2>
          <div class="mt-6 space-y-4">
            <div v-for="item in planMix" :key="item.label">
              <div class="mb-2 flex justify-between text-xs"><span class="capitalize text-slate-300">{{ item.label }}</span><span class="text-slate-500">{{ number(item.total) }}</span></div>
              <div class="h-2 rounded-full bg-slate-800"><div class="h-2 rounded-full bg-violet-300" :style="{ width: barWidth(item.total, maxPlan) }" /></div>
            </div>
            <p v-if="!planMix.length" class="text-sm text-slate-500">No plan data yet.</p>
          </div>
        </article>
      </section>

      <section class="grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
        <article class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04]">
          <div class="flex items-center justify-between border-b border-white/10 px-6 py-5"><div><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Latest tenants</p><h2 class="mt-1 text-lg font-semibold">Recent companies</h2></div><span class="rounded-full bg-white/5 px-3 py-1 text-xs text-slate-400">{{ number(companies.length) }} total</span></div>
          <div class="overflow-x-auto"><table class="w-full min-w-[560px] text-left text-sm"><thead class="text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-3 font-medium">Company</th><th class="px-6 py-3 font-medium">Environment</th><th class="px-6 py-3 font-medium">Status</th><th class="px-6 py-3 font-medium">Joined</th></tr></thead><tbody><tr v-for="company in recentCompanies" :key="company.id" class="border-t border-white/5"><td class="px-6 py-4 font-medium">{{ company.name || 'Unnamed company' }}</td><td class="px-6 py-4 capitalize text-slate-400">{{ company.environment || '—' }}</td><td class="px-6 py-4"><span class="rounded-full bg-emerald-300/10 px-2.5 py-1 text-xs text-emerald-200">{{ company.status || '—' }}</span></td><td class="px-6 py-4 text-slate-400">{{ date(company.created_at) }}</td></tr><tr v-if="!recentCompanies.length"><td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">No companies found.</td></tr></tbody></table></div>
        </article>

        <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6">
          <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tenant scale</p><h2 class="mt-1 text-lg font-semibold">Largest communities</h2>
          <div class="mt-6 space-y-4"><div v-for="company in topCompanies" :key="company.id" class="flex items-center gap-3"><div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-cyan-300/10 text-sm font-semibold text-cyan-200">{{ (company.name || '?').slice(0, 1).toUpperCase() }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ company.name || 'Unnamed company' }}</p><p class="text-xs text-slate-500">{{ number(company.users_count) }} users</p></div><span class="text-xs capitalize text-slate-500">{{ company.environment || '—' }}</span></div><p v-if="!topCompanies.length" class="text-sm text-slate-500">No company data yet.</p></div>
        </article>
      </section>

      <section class="grid gap-5 lg:grid-cols-2">
        <article class="rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-400/10 to-white/[0.03] p-6"><p class="text-xs uppercase tracking-[0.2em] text-indigo-200/70">Access snapshot</p><h2 class="mt-2 text-2xl font-semibold">Keep every tenant moving.</h2><p class="mt-3 max-w-lg text-sm leading-6 text-slate-400">Use Companies to manage lifecycle and domains, Roles to govern access, and Billing to keep plans aligned.</p><div class="mt-6 flex flex-wrap gap-3"><RouterLink to="/dashboard/super/companies" class="rounded-full bg-indigo-300 px-4 py-2 text-xs font-semibold text-slate-950">Manage companies</RouterLink><RouterLink to="/dashboard/super/billing" class="rounded-full border border-white/10 px-4 py-2 text-xs font-semibold text-slate-200">Open billing</RouterLink></div></article>
        <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6"><p class="text-xs uppercase tracking-[0.2em] text-slate-500">Recently active</p><h2 class="mt-2 text-lg font-semibold">New user accounts</h2><div class="mt-5 space-y-3"><div v-for="user in recentUsers.slice(0, 5)" :key="user.id" class="flex items-center justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-medium">{{ user.name || user.email }}</p><p class="truncate text-xs text-slate-500">{{ user.company?.name || user.email || '—' }}</p></div><span class="shrink-0 text-xs text-slate-500">{{ date(user.created_at) }}</span></div><p v-if="!recentUsers.length" class="text-sm text-slate-500">No recent users found.</p></div></article>
      </section>
    </div>
  </main>
</template>
