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
const isOnboarding = ref(false);
const isSubmitting = ref(false);
const onboardingSuccess = ref('');

const newOnboardingForm = () => ({
  name: '',
  domain: '',
  industry: '',
  country: '',
  email: '',
  phone: '',
  plan_id: '',
  status: 'active',
  environment: 'demo',
  seed_demo: true,
  admin_name: '',
  admin_email: '',
  hr_name: '',
  hr_email: '',
});

const onboardingForm = ref(newOnboardingForm());

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

const openOnboarding = () => {
  errorMessage.value = '';
  onboardingSuccess.value = '';
  isOnboarding.value = true;
};

const closeOnboarding = () => {
  if (!isSubmitting.value) isOnboarding.value = false;
};

const submitOnboarding = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';
  onboardingSuccess.value = '';
  try {
    await apiClient.post('/api/companies', {
      ...onboardingForm.value,
      plan_id: onboardingForm.value.plan_id ? Number(onboardingForm.value.plan_id) : null,
    });
    isOnboarding.value = false;
    onboardingForm.value = newOnboardingForm();
    onboardingSuccess.value = 'Company created. Login invitations were sent to the Company Admin and HR contacts.';
    await loadCompanies();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to onboard company.';
  } finally {
    isSubmitting.value = false;
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
          <button
            class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-emerald-200"
            type="button"
            @click="openOnboarding"
          >
            Onboard a company
          </button>
        </div>
      </section>

      <div v-if="isOnboarding" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/80 px-4 py-6 backdrop-blur-sm" @click.self="closeOnboarding">
        <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-white/10 bg-slate-900 p-6 shadow-2xl shadow-black/50 sm:p-8" role="dialog" aria-modal="true" aria-labelledby="onboard-company-title">
          <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">New tenant</p><h2 id="onboard-company-title" class="mt-2 text-2xl font-semibold">Onboard a company</h2><p class="mt-2 text-sm text-slate-400">Create the company, its Company Admin, and its HR contact. Each person receives a secure login invitation by email.</p></div>
            <button class="rounded-full border border-white/10 px-3 py-1 text-slate-300 hover:bg-white/10" type="button" aria-label="Close onboarding dialog" @click="closeOnboarding">✕</button>
          </div>
          <form class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submitOnboarding">
            <label class="text-sm text-slate-300">Company name<input v-model="onboardingForm.name" required class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" /></label>
            <label class="text-sm text-slate-300">Domain<input v-model="onboardingForm.domain" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" placeholder="example.com" /></label>
            <label class="text-sm text-slate-300">Industry<input v-model="onboardingForm.industry" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" /></label>
            <label class="text-sm text-slate-300">Country code<input v-model="onboardingForm.country" maxlength="2" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 uppercase outline-none" placeholder="KE" /></label>
            <label class="text-sm text-slate-300">Company email<input v-model="onboardingForm.email" type="email" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" placeholder="contact@example.com" /></label>
            <label class="text-sm text-slate-300">Company phone<input v-model="onboardingForm.phone" type="tel" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" placeholder="+254700000000" /></label>
            <label class="text-sm text-slate-300">Environment<select v-model="onboardingForm.environment" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none"><option value="demo">Demo</option><option value="staging">Staging</option><option value="production">Production</option></select></label>
            <label class="text-sm text-slate-300">Status<select v-model="onboardingForm.status" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            <label class="text-sm text-slate-300">Company Admin name<input v-model="onboardingForm.admin_name" required class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" /></label>
            <label class="text-sm text-slate-300">Company Admin email<input v-model="onboardingForm.admin_email" required type="email" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" /></label>
            <label class="text-sm text-slate-300">HR name<input v-model="onboardingForm.hr_name" required class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none" /></label>
            <label class="text-sm text-slate-300">HR email<input v-model="onboardingForm.hr_email" required type="email" :disabled="isSubmitting" class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 outline-none disabled:opacity-50" /></label>
            <p class="rounded-xl border border-cyan-300/15 bg-cyan-300/5 px-3 py-2 text-xs leading-5 text-cyan-100 sm:col-span-2">Passwords are not required. The Company Admin and HR contact each receive a one-time login link by email.</p>
            <label class="flex items-center gap-2 text-sm text-slate-300 sm:col-span-2"><input v-model="onboardingForm.seed_demo" type="checkbox" class="h-4 w-4 accent-emerald-400" /> Seed demo data</label>
            <div class="flex justify-end gap-3 sm:col-span-2"><button class="rounded-full border border-white/10 px-4 py-2 text-sm text-slate-300" type="button" :disabled="isSubmitting" @click="closeOnboarding">Cancel</button><button class="rounded-full bg-emerald-300 px-5 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Creating…' : 'Create company' }}</button></div>
          </form>
        </section>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>
      <p
        v-if="onboardingSuccess"
        class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100"
      >
        {{ onboardingSuccess }}
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
