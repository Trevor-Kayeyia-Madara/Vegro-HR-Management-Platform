<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'TaxProfilesPage' });

const profiles = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeProfile = ref(null);
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
  name: '',
  country_code: '',
  currency: '',
  base_currency: '',
  exchange_rate_to_base: '',
  personal_relief: '',
  insurance_relief_rate: '',
  insurance_relief_cap: '',
  pension_cap: '',
  mortgage_cap: '',
  nssf_rate: '',
  nssf_tier1_limit: '',
  nssf_tier2_limit: '',
  nssf_max: '',
  shif_rate: '',
  shif_min: '',
  housing_levy_rate: '',
  paye_bands: [],
});

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

const loadProfiles = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/tax-profiles', {
      params: { page: currentPage.value, per_page: pageSize.value },
    });
    const parsed = parsePaginated(response);
    profiles.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load tax profiles.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activeProfile.value = null;
  form.value = {
    name: '',
    country_code: '',
    currency: 'KES',
    base_currency: 'USD',
    exchange_rate_to_base: '1',
    personal_relief: '',
    insurance_relief_rate: '',
    insurance_relief_cap: '',
    pension_cap: '',
    mortgage_cap: '',
    nssf_rate: '',
    nssf_tier1_limit: '',
    nssf_tier2_limit: '',
    nssf_max: '',
    shif_rate: '',
    shif_min: '',
    housing_levy_rate: '',
    paye_bands: [],
  };
  isModalOpen.value = true;
};

const openEdit = (profile) => {
  modalMode.value = 'edit';
  activeProfile.value = profile;
  form.value = {
    name: profile?.name || '',
    country_code: profile?.country_code || '',
    currency: profile?.currency || '',
    base_currency: profile?.base_currency || '',
    exchange_rate_to_base: profile?.exchange_rate_to_base ?? '',
    personal_relief: profile?.personal_relief ?? '',
    insurance_relief_rate: profile?.insurance_relief_rate ?? '',
    insurance_relief_cap: profile?.insurance_relief_cap ?? '',
    pension_cap: profile?.pension_cap ?? '',
    mortgage_cap: profile?.mortgage_cap ?? '',
    nssf_rate: profile?.nssf_rate ?? '',
    nssf_tier1_limit: profile?.nssf_tier1_limit ?? '',
    nssf_tier2_limit: profile?.nssf_tier2_limit ?? '',
    nssf_max: profile?.nssf_max ?? '',
    shif_rate: profile?.shif_rate ?? '',
    shif_min: profile?.shif_min ?? '',
    housing_levy_rate: profile?.housing_levy_rate ?? '',
    paye_bands: (profile?.paye_bands || []).map((band) => ({
      limit: band.limit ?? '',
      rate: band.rate ?? '',
    })),
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
    const cleanedBands = form.value.paye_bands
      .filter((band) => band.rate !== '' || band.limit !== '')
      .map((band) => ({
        limit: band.limit === '' ? null : Number(band.limit),
        rate: Number(band.rate),
      }))
      .sort((a, b) => {
        if (a.limit === null) return 1;
        if (b.limit === null) return -1;
        return a.limit - b.limit;
      });

    const payload = {
      name: form.value.name,
      country_code: String(form.value.country_code || '').toUpperCase(),
      currency: String(form.value.currency || '').toUpperCase(),
      base_currency: form.value.base_currency ? String(form.value.base_currency).toUpperCase() : null,
      exchange_rate_to_base: form.value.exchange_rate_to_base === '' ? null : Number(form.value.exchange_rate_to_base),
      personal_relief: Number(form.value.personal_relief || 0),
      insurance_relief_rate: Number(form.value.insurance_relief_rate || 0),
      insurance_relief_cap: Number(form.value.insurance_relief_cap || 0),
      pension_cap: Number(form.value.pension_cap || 0),
      mortgage_cap: Number(form.value.mortgage_cap || 0),
      nssf_rate: Number(form.value.nssf_rate || 0),
      nssf_tier1_limit: Number(form.value.nssf_tier1_limit || 0),
      nssf_tier2_limit: Number(form.value.nssf_tier2_limit || 0),
      nssf_max: Number(form.value.nssf_max || 0),
      shif_rate: Number(form.value.shif_rate || 0),
      shif_min: Number(form.value.shif_min || 0),
      housing_levy_rate: Number(form.value.housing_levy_rate || 0),
      paye_bands: cleanedBands,
    };

    if (modalMode.value === 'create') {
      await apiClient.post('/api/tax-profiles', payload);
    } else if (activeProfile.value?.id) {
      await apiClient.put(`/api/tax-profiles/${activeProfile.value.id}`, payload);
    }

    await loadProfiles();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save tax profile.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteProfile = async (profile) => {
  const confirmed = window.confirm(`Delete ${profile?.name || 'this profile'}?`);
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/tax-profiles/${profile.id}`);
    await loadProfiles();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete tax profile.';
  }
};

const addBand = () => {
  form.value.paye_bands.push({ limit: '', rate: '' });
};

const removeBand = (index) => {
  form.value.paye_bands.splice(index, 1);
};

const filteredProfiles = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return profiles.value;
  return profiles.value.filter((profile) => {
    const name = profile?.name?.toLowerCase() || '';
    const country = profile?.country_code?.toLowerCase() || '';
    return name.includes(query) || country.includes(query);
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadProfiles();
};

onMounted(loadProfiles);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Tax Profiles</p>
          <h1 class="text-3xl font-semibold">Payroll Tax Profiles</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Define country-specific PAYE rules and statutory deductions.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search profiles..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add profile
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <div class="overflow-hidden rounded-3xl border border-white/10 bg-white/5">
        <div class="max-h-[72vh] overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-left text-xs sm:text-sm">
            <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="px-6 py-4 font-medium">Name</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Country</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Currency</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Base</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">FX Rate</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Relief</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Housing Levy</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="8">
                  Loading tax profiles...
                </td>
              </tr>
              <tr
                v-for="profile in filteredProfiles"
                :key="profile.id"
                class="hover:bg-white/5"
              >
                <td class="px-6 py-4 font-medium text-slate-100">{{ profile.name }}</td>
                <td class="px-6 py-4 text-slate-200/80 hidden md:table-cell">{{ profile.country_code }}</td>
                <td class="px-6 py-4 text-slate-200/80 hidden md:table-cell">{{ profile.currency }}</td>
                <td class="px-6 py-4 text-slate-200/80 hidden lg:table-cell">{{ profile.base_currency || '—' }}</td>
                <td class="px-6 py-4 text-slate-200/80 hidden lg:table-cell">{{ profile.exchange_rate_to_base ? Number(profile.exchange_rate_to_base).toFixed(6) : '—' }}</td>
                <td class="px-6 py-4 text-slate-200/80 hidden lg:table-cell">
                  {{ profile.personal_relief ? Number(profile.personal_relief).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4 text-slate-200/80 hidden lg:table-cell">
                  {{ profile.housing_levy_rate ? `${Number(profile.housing_levy_rate) * 100}%` : '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(profile)"
                    >
                      Edit
                    </button>
                    <button
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deleteProfile(profile)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredProfiles.length">
                <td class="px-6 py-6 text-center text-slate-400" colspan="8">
                  No tax profiles found yet.
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
          {{ filteredProfiles.length }}
          of
          {{ pagination.total }}
          profiles
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
        class="vegro-modal-overlay"
        @click="closeModal"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isModalOpen" class="vegro-modal-wrap">
        <div class="vegro-modal">
          <div class="vegro-modal-header">
            <div>
              <p class="vegro-modal-title">
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Tax Profile
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Profile' : 'Update Profile' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Name</span>
              <input
                v-model="form.name"
                type="text"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Country Code</span>
              <input
                v-model="form.country_code"
                type="text"
                maxlength="2"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white uppercase outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Currency</span>
              <input
                v-model="form.currency"
                type="text"
                maxlength="3"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white uppercase outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Base Currency</span>
              <input
                v-model="form.base_currency"
                type="text"
                maxlength="3"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white uppercase outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>FX Rate to Base</span>
              <input
                v-model="form.exchange_rate_to_base"
                type="number"
                min="0.000001"
                step="0.000001"
                placeholder="e.g. 0.007750"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Personal Relief</span>
              <input
                v-model="form.personal_relief"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Insurance Relief Rate</span>
              <input
                v-model="form.insurance_relief_rate"
                type="number"
                step="0.0001"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Insurance Relief Cap</span>
              <input
                v-model="form.insurance_relief_cap"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Pension Cap</span>
              <input
                v-model="form.pension_cap"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Mortgage Cap</span>
              <input
                v-model="form.mortgage_cap"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>NSSF Rate</span>
              <input
                v-model="form.nssf_rate"
                type="number"
                step="0.0001"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>NSSF Tier 1 Limit</span>
              <input
                v-model="form.nssf_tier1_limit"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>NSSF Tier 2 Limit</span>
              <input
                v-model="form.nssf_tier2_limit"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>NSSF Max</span>
              <input
                v-model="form.nssf_max"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>SHIF Rate</span>
              <input
                v-model="form.shif_rate"
                type="number"
                step="0.0001"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>SHIF Minimum</span>
              <input
                v-model="form.shif_min"
                type="number"
                step="0.01"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Housing Levy Rate</span>
              <input
                v-model="form.housing_levy_rate"
                type="number"
                step="0.0001"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>PAYE Bands</span>
              <div class="flex flex-col gap-3 rounded-xl border border-white/10 bg-slate-950/40 p-4">
                <div
                  v-for="(band, index) in form.paye_bands"
                  :key="index"
                  class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                >
                  <label class="flex flex-col gap-2 text-xs text-slate-300">
                    <span>Limit (KES)</span>
                    <input
                      v-model="band.limit"
                      type="number"
                      step="0.01"
                      placeholder="Leave blank for infinity"
                      class="h-10 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-xs text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                    />
                  </label>
                  <label class="flex flex-col gap-2 text-xs text-slate-300">
                    <span>Rate (decimal)</span>
                    <input
                      v-model="band.rate"
                      type="number"
                      step="0.0001"
                      placeholder="e.g. 0.1"
                      class="h-10 rounded-xl border border-white/10 bg-slate-950/60 px-3 text-xs text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                    />
                  </label>
                  <button
                    class="mt-6 h-10 rounded-full border border-rose-500/30 bg-rose-500/10 px-3 text-xs text-rose-200 transition hover:bg-rose-500/20"
                    type="button"
                    @click="removeBand(index)"
                  >
                    Remove
                  </button>
                </div>
                <button
                  class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
                  type="button"
                  @click="addBand"
                >
                  Add band
                </button>
              </div>
            </label>

            <button
              class="sm:col-span-2 mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save profile' }}
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






