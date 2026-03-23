<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import useAuth from '../../hooks/useAuth';
import recruitmentService from '../../services/recruitmentService';

defineOptions({ name: 'CandidatesPanel' });

const { hasPermission } = useAuth();
const canManage = computed(() => hasPermission('recruitment.manage'));

const candidates = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const searchQuery = ref('');

const loadCandidates = async () => {
  if (!canManage.value) return;
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await recruitmentService.getCandidates({
      per_page: 25,
      ...(searchQuery.value.trim() ? { q: searchQuery.value.trim() } : {}),
    });
    candidates.value = recruitmentService.parsePaginated(response).items;
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load candidates.';
  } finally {
    isLoading.value = false;
  }
};

const modalOpen = ref(false);
const modalMode = ref('create');
const activeCandidate = ref(null);
const isSubmitting = ref(false);
const formError = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  source: '',
  linkedin_url: '',
  notes: '',
  consent_at: '',
});

const openCreate = () => {
  if (!canManage.value) return;
  modalMode.value = 'create';
  activeCandidate.value = null;
  form.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    source: '',
    linkedin_url: '',
    notes: '',
    consent_at: '',
  };
  formError.value = '';
  modalOpen.value = true;
};

const openEdit = (candidate) => {
  if (!canManage.value) return;
  modalMode.value = 'edit';
  activeCandidate.value = candidate;
  form.value = {
    first_name: candidate?.first_name || '',
    last_name: candidate?.last_name || '',
    email: candidate?.email || '',
    phone: candidate?.phone || '',
    source: candidate?.source || '',
    linkedin_url: candidate?.linkedin_url || '',
    notes: candidate?.notes || '',
    consent_at: candidate?.consent_at ? String(candidate.consent_at).slice(0, 10) : '',
  };
  formError.value = '';
  modalOpen.value = true;
};

const closeModal = () => {
  modalOpen.value = false;
  activeCandidate.value = null;
};

const submitCandidate = async () => {
  if (!canManage.value) return;
  isSubmitting.value = true;
  formError.value = '';

  try {
    const payload = {
      first_name: form.value.first_name,
      last_name: form.value.last_name || null,
      email: form.value.email,
      phone: form.value.phone || null,
      source: form.value.source || null,
      linkedin_url: form.value.linkedin_url || null,
      notes: form.value.notes || null,
      consent_at: form.value.consent_at || null,
    };
    if (modalMode.value === 'create') {
      await recruitmentService.createCandidate(payload);
    } else if (activeCandidate.value?.id) {
      await recruitmentService.updateCandidate(activeCandidate.value.id, payload);
    }
    closeModal();
    await loadCandidates();
  } catch (error) {
    formError.value = error?.response?.data?.message || 'Unable to save candidate.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteCandidate = async (candidate) => {
  if (!canManage.value || !candidate?.id) return;
  // eslint-disable-next-line no-alert
  if (!window.confirm(`Delete candidate "${candidate.first_name} ${candidate.last_name || ''}"?`)) return;
  try {
    await recruitmentService.deleteCandidate(candidate.id);
    await loadCandidates();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete candidate.';
  }
};

onMounted(async () => {
  await loadCandidates();
});

watch(searchQuery, () => {
  loadCandidates();
});
</script>

<template>
  <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
    <div v-if="!canManage" class="text-sm text-slate-300/80">
      Candidate profiles are restricted to HR/Admin.
    </div>

    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <label class="text-xs text-slate-300/80">
          <span class="mr-2 uppercase tracking-[0.2em] text-slate-400">Search</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Name, email, phone"
            class="h-10 w-72 rounded-2xl border border-white/10 bg-slate-950/30 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
          />
        </label>

        <div class="flex items-center gap-2">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="loadCandidates"
          >
            Refresh
          </button>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            New candidate
          </button>
        </div>
      </div>

      <p
        v-if="errorMessage"
        class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <div v-if="isLoading" class="mt-6 text-sm text-slate-300/80">Loading candidates...</div>

      <div v-else class="mt-6 overflow-hidden rounded-3xl border border-white/10 bg-slate-950/30">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-slate-200">
            <thead class="bg-white/5 text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-4 py-3 text-left font-semibold">Name</th>
                <th class="px-4 py-3 text-left font-semibold">Email</th>
                <th class="px-4 py-3 text-left font-semibold">Phone</th>
                <th class="px-4 py-3 text-left font-semibold">Source</th>
                <th class="px-4 py-3 text-right font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="candidate in candidates" :key="candidate.id" class="hover:bg-white/5">
                <td class="px-4 py-3">
                  <div class="font-medium text-white">
                    {{ candidate.first_name }} {{ candidate.last_name }}
                  </div>
                </td>
                <td class="px-4 py-3 text-slate-300">{{ candidate.email }}</td>
                <td class="px-4 py-3 text-slate-300">{{ candidate.phone || '—' }}</td>
                <td class="px-4 py-3 text-slate-300">{{ candidate.source || '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(candidate)"
                    >
                      Edit
                    </button>
                    <button
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-100 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deleteCandidate(candidate)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!candidates.length">
                <td class="px-4 py-6 text-center text-slate-400" colspan="5">No candidates yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </section>

  <div
    v-if="modalOpen"
    class="vegro-modal-viewport"
    @click.self="closeModal"
  >
    <div class="vegro-modal max-w-3xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
            {{ modalMode === 'create' ? 'Create candidate' : 'Edit candidate' }}
          </p>
          <h2 class="mt-2 text-2xl font-semibold">{{ form.first_name || 'Candidate' }}</h2>
          <p class="mt-1 text-sm text-slate-300/70">Store candidate contact and consent metadata.</p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="closeModal"
        >
          Close
        </button>
      </div>

      <p
        v-if="formError"
        class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ formError }}
      </p>

      <form class="mt-6 max-h-[62dvh] overflow-y-auto pr-1 grid gap-4 md:grid-cols-2" @submit.prevent="submitCandidate">
        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">First name</span>
          <input
            v-model="form.first_name"
            required
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Last name</span>
          <input
            v-model="form.last_name"
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Email</span>
          <input
            v-model="form.email"
            required
            type="email"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Phone</span>
          <input
            v-model="form.phone"
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Source</span>
          <input
            v-model="form.source"
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">LinkedIn URL</span>
          <input
            v-model="form.linkedin_url"
            type="url"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Notes</span>
          <textarea
            v-model="form.notes"
            rows="4"
            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          ></textarea>
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Consent date</span>
          <input
            v-model="form.consent_at"
            type="date"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <div class="md:col-span-2 flex items-center justify-end gap-3">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeModal"
          >
            Cancel
          </button>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
            type="submit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Saving...' : 'Save candidate' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
