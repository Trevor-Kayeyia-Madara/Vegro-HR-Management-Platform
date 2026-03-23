<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'SuperAdminCompaniesPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const companies = ref([]);
const search = ref('');
const statusFilter = ref('all');
const environmentFilter = ref('all');

const formatCount = (value) => Number(value || 0).toLocaleString();

const loadCompanies = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.get('/api/companies');
    companies.value = response?.data?.data || [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load companies.';
  } finally {
    isLoading.value = false;
  }
};

const filteredCompanies = computed(() => {
  const term = search.value.trim().toLowerCase();
  return companies.value.filter((company) => {
    const matchesTerm = !term || company.name?.toLowerCase().includes(term);
    const matchesStatus = statusFilter.value === 'all' || company.status === statusFilter.value;
    const matchesEnvironment =
      environmentFilter.value === 'all' || company.environment === environmentFilter.value;
    return matchesTerm && matchesStatus && matchesEnvironment;
  });
});

const activeCompanies = computed(() => companies.value.filter((company) => company.status === 'active').length);
const demoCompanies = computed(() => companies.value.filter((company) => company.environment === 'demo').length);
const productionCompanies = computed(
  () => companies.value.filter((company) => company.environment === 'production').length,
);

const toggleCompanyStatus = async (company) => {
  if (!company) return;
  errorMessage.value = '';
  try {
    if (company.status === 'active') {
      await apiClient.post(`/api/companies/${company.id}/suspend`);
    } else {
      await apiClient.post(`/api/companies/${company.id}/resume`);
    }
    await loadCompanies();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update company status.';
  }
};

onMounted(loadCompanies);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <section class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Super Admin
        </p>
        <h1 class="text-3xl font-semibold sm:text-4xl">Companies</h1>
        <p class="max-w-2xl text-sm text-slate-300/70 sm:text-base">
          Control tenant access, environments, and licenses. Company admins handle roles inside
          their own tenant.
        </p>
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300/80">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 transition hover:bg-white/10"
            type="button"
            @click="loadCompanies"
          >
            Refresh list
          </button>
          <RouterLink
            class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-emerald-200"
            to="/dashboard/super"
          >
            Onboard a company
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
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Total companies</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(companies.length) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Active</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(activeCompanies) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Demo</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(demoCompanies) }}</p>
        </article>
        <article class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Production</p>
          <p class="mt-3 text-3xl font-semibold">{{ formatCount(productionCompanies) }}</p>
        </article>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Directory</p>
            <h2 class="mt-2 text-lg font-semibold">Manage tenants</h2>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <input
              v-model="search"
              class="h-10 w-64 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
              placeholder="Search company"
            />
            <select
              v-model="statusFilter"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
            >
              <option value="all">All status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
            <select
              v-model="environmentFilter"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
            >
              <option value="all">All environments</option>
              <option value="demo">Demo</option>
              <option value="staging">Staging</option>
              <option value="production">Production</option>
            </select>
          </div>
        </div>

        <div class="mt-5 overflow-x-auto">
          <table class="min-w-[760px] text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Company</th>
                <th class="px-3 py-2">Environment</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Users</th>
                <th class="px-3 py-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="company in filteredCompanies" :key="company.id" class="border-t border-white/5">
                <td class="px-3 py-2">
                  <p class="font-semibold">{{ company.name }}</p>
                  <p class="text-[11px] text-slate-400">ID: {{ company.id }}</p>
                </td>
                <td class="px-3 py-2">
                  <span class="rounded-full bg-white/5 px-2 py-1 text-[10px]">
                    {{ company.environment || 'demo' }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <span
                    class="rounded-full px-2 py-1 text-[10px]"
                    :class="company.status === 'active'
                      ? 'bg-emerald-400/10 text-emerald-200'
                      : 'bg-rose-400/10 text-rose-200'"
                  >
                    {{ company.status || 'inactive' }}
                  </span>
                </td>
                <td class="px-3 py-2">{{ formatCount(company.users_count) }}</td>
                <td class="px-3 py-2">
                  <button
                    class="rounded-full border border-white/10 px-3 py-1 text-[10px] text-slate-200"
                    type="button"
                    @click="toggleCompanyStatus(company)"
                  >
                    {{ company.status === 'active' ? 'Suspend' : 'Resume' }}
                  </button>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredCompanies.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="5">
                  No companies matched your filters.
                </td>
              </tr>
              <tr v-if="isLoading">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="5">
                  Loading companies...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>
