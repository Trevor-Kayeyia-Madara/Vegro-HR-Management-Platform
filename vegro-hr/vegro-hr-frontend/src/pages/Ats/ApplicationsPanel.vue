<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import useAuth from '../../hooks/useAuth';
import recruitmentService from '../../services/recruitmentService';

defineOptions({ name: 'ApplicationsPanel' });

const { hasPermission, hasRole } = useAuth();

const canManage = computed(() => hasPermission('recruitment.manage'));
const canProgress = computed(() => hasRole(['admin', 'hr', 'manager']));

const applications = ref([]);
const jobs = ref([]);
const candidates = ref([]);

const isLoading = ref(true);
const errorMessage = ref('');

const stageFilter = ref('');
const jobFilter = ref('');

const stages = [
  { value: 'applied', label: 'Applied' },
  { value: 'screening', label: 'Screening' },
  { value: 'interview', label: 'Interview' },
  { value: 'offer', label: 'Offer' },
  { value: 'hired', label: 'Hired' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'withdrawn', label: 'Withdrawn' },
];

const loadJobs = async () => {
  try {
    const response = await recruitmentService.getJobs({ per_page: 1000 });
    jobs.value = recruitmentService.parsePaginated(response).items;
  } catch {
    jobs.value = [];
  }
};

const loadCandidates = async () => {
  if (!canManage.value) return;
  try {
    const response = await recruitmentService.getCandidates({ per_page: 1000 });
    candidates.value = recruitmentService.parsePaginated(response).items;
  } catch {
    candidates.value = [];
  }
};

const loadApplications = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await recruitmentService.getApplications({
      per_page: 25,
      ...(stageFilter.value ? { stage: stageFilter.value } : {}),
      ...(jobFilter.value ? { job_posting_id: jobFilter.value } : {}),
    });
    applications.value = recruitmentService.parsePaginated(response).items;
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load applications.';
  } finally {
    isLoading.value = false;
  }
};

const updateStage = async (application, stage) => {
  if (!canProgress.value || !application?.id) return;
  try {
    await recruitmentService.updateApplication(application.id, { stage });
    await loadApplications();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update stage.';
  }
};

const updateRating = async (application, rating) => {
  if (!canProgress.value || !application?.id) return;
  try {
    await recruitmentService.updateApplication(application.id, { rating });
    await loadApplications();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update rating.';
  }
};

const noteModalOpen = ref(false);
const noteApplication = ref(null);
const noteText = ref('');
const noteError = ref('');
const isSavingNote = ref(false);

const openNote = (application) => {
  if (!canProgress.value) return;
  noteApplication.value = application;
  noteText.value = '';
  noteError.value = '';
  noteModalOpen.value = true;
};

const closeNote = () => {
  noteModalOpen.value = false;
  noteApplication.value = null;
};

const saveNote = async () => {
  if (!noteApplication.value?.id) return;
  isSavingNote.value = true;
  noteError.value = '';
  try {
    await recruitmentService.addApplicationNote(noteApplication.value.id, { note: noteText.value });
    closeNote();
  } catch (error) {
    noteError.value = error?.response?.data?.message || 'Unable to save note.';
  } finally {
    isSavingNote.value = false;
  }
};

const createModalOpen = ref(false);
const isCreating = ref(false);
const createError = ref('');
const createForm = ref({
  job_posting_id: '',
  candidate_id: '',
  stage: 'applied',
});

const openCreate = async () => {
  if (!canManage.value) return;
  createForm.value = { job_posting_id: '', candidate_id: '', stage: 'applied' };
  createError.value = '';
  await Promise.all([loadJobs(), loadCandidates()]);
  createModalOpen.value = true;
};

const closeCreate = () => {
  createModalOpen.value = false;
};

const submitCreate = async () => {
  if (!canManage.value) return;
  isCreating.value = true;
  createError.value = '';
  try {
    await recruitmentService.createApplication({
      job_posting_id: Number(createForm.value.job_posting_id),
      candidate_id: Number(createForm.value.candidate_id),
      stage: createForm.value.stage || null,
    });
    closeCreate();
    await loadApplications();
  } catch (error) {
    createError.value = error?.response?.data?.message || 'Unable to create application.';
  } finally {
    isCreating.value = false;
  }
};

onMounted(async () => {
  await Promise.all([loadJobs(), loadCandidates(), loadApplications()]);
});

watch([stageFilter, jobFilter], () => {
  loadApplications();
});
</script>

<template>
  <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <label class="text-xs text-slate-300/80">
          <span class="mr-2 uppercase tracking-[0.2em] text-slate-400">Stage</span>
          <select
            v-model="stageFilter"
            class="h-10 rounded-2xl border border-white/10 bg-slate-950/30 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
          >
            <option value="">All</option>
            <option v-for="stage in stages" :key="stage.value" :value="stage.value">
              {{ stage.label }}
            </option>
          </select>
        </label>

        <label class="text-xs text-slate-300/80">
          <span class="mr-2 uppercase tracking-[0.2em] text-slate-400">Job</span>
          <select
            v-model="jobFilter"
            class="h-10 min-w-56 rounded-2xl border border-white/10 bg-slate-950/30 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
          >
            <option value="">All</option>
            <option v-for="job in jobs" :key="job.id" :value="String(job.id)">
              {{ job.title }}
            </option>
          </select>
        </label>
      </div>

      <div class="flex items-center gap-2">
        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="loadApplications"
        >
          Refresh
        </button>
        <button
          v-if="canManage"
          class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          type="button"
          @click="openCreate"
        >
          New application
        </button>
      </div>
    </div>

    <p
      v-if="errorMessage"
      class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
    >
      {{ errorMessage }}
    </p>

    <div v-if="isLoading" class="mt-6 text-sm text-slate-300/80">Loading applications...</div>

    <div v-else class="mt-6 grid gap-4 lg:grid-cols-2">
      <article
        v-for="application in applications"
        :key="application.id"
        class="rounded-3xl border border-white/10 bg-slate-950/30 p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">
              {{ application?.job?.title || 'Job' }}
            </p>
            <h3 class="mt-2 text-lg font-semibold text-white">
              {{ application?.candidate?.first_name }} {{ application?.candidate?.last_name }}
            </h3>
            <p class="mt-1 text-xs text-slate-400/80">
              {{ application?.candidate?.email || '—' }}
              <span v-if="application?.candidate?.phone">· {{ application.candidate.phone }}</span>
            </p>
          </div>
          <div class="text-right text-xs text-slate-400/80">
            <p class="uppercase tracking-[0.2em]">Stage</p>
            <p class="mt-1 text-sm text-slate-200">{{ application.stage }}</p>
          </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <label class="block text-[11px] uppercase tracking-[0.2em] text-slate-400 sm:col-span-2">
            <span class="mb-2 block">Stage</span>
            <select
              v-if="canProgress"
              :value="application.stage"
              class="h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
              @change="updateStage(application, $event.target.value)"
            >
              <option v-for="stage in stages" :key="stage.value" :value="stage.value">
                {{ stage.label }}
              </option>
            </select>
            <div v-else class="h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 flex items-center text-sm text-slate-200">
              {{ application.stage }}
            </div>
          </label>

          <label class="block text-[11px] uppercase tracking-[0.2em] text-slate-400">
            <span class="mb-2 block">Rating</span>
            <select
              v-if="canProgress"
              :value="application.rating ?? ''"
              class="h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
              @change="updateRating(application, $event.target.value ? Number($event.target.value) : null)"
            >
              <option value="">—</option>
              <option v-for="n in 5" :key="n" :value="n">{{ n }}</option>
            </select>
            <div v-else class="h-10 w-full rounded-2xl border border-white/10 bg-white/5 px-3 flex items-center text-sm text-slate-200">
              {{ application.rating || '—' }}
            </div>
          </label>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button
            v-if="canProgress"
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="openNote(application)"
          >
            Add note
          </button>
        </div>
      </article>

      <div
        v-if="!applications.length"
        class="rounded-3xl border border-white/10 bg-slate-950/30 p-8 text-sm text-slate-400 lg:col-span-2"
      >
        No applications yet.
      </div>
    </div>
  </section>

  <div
    v-if="noteModalOpen"
    class="vegro-modal-viewport"
    @click.self="closeNote"
  >
    <div class="vegro-modal max-w-2xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Application note</p>
          <h2 class="mt-2 text-2xl font-semibold">
            {{ noteApplication?.candidate?.first_name }} {{ noteApplication?.candidate?.last_name }}
          </h2>
          <p class="mt-1 text-sm text-slate-300/70">{{ noteApplication?.job?.title }}</p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="closeNote"
        >
          Close
        </button>
      </div>

      <p
        v-if="noteError"
        class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ noteError }}
      </p>

      <form class="mt-6 max-h-[62dvh] space-y-4 overflow-y-auto pr-1" @submit.prevent="saveNote">
        <textarea
          v-model="noteText"
          required
          rows="6"
          class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          placeholder="Write your note..."
        ></textarea>

        <div class="flex items-center justify-end gap-3">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeNote"
          >
            Cancel
          </button>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
            type="submit"
            :disabled="isSavingNote"
          >
            {{ isSavingNote ? 'Saving...' : 'Save note' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  <div
    v-if="createModalOpen"
    class="vegro-modal-viewport"
    @click.self="closeCreate"
  >
    <div class="vegro-modal max-w-2xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">New application</p>
          <h2 class="mt-2 text-2xl font-semibold">Link candidate to job</h2>
          <p class="mt-1 text-sm text-slate-300/70">Creates the first stage event automatically.</p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="closeCreate"
        >
          Close
        </button>
      </div>

      <p
        v-if="createError"
        class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ createError }}
      </p>

      <form class="mt-6 max-h-[62dvh] overflow-y-auto pr-1 grid gap-4" @submit.prevent="submitCreate">
        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Job posting</span>
          <select
            v-model="createForm.job_posting_id"
            required
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option value="" disabled>Select job</option>
            <option v-for="job in jobs" :key="job.id" :value="String(job.id)">
              {{ job.title }}
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Candidate</span>
          <select
            v-model="createForm.candidate_id"
            required
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option value="" disabled>Select candidate</option>
            <option v-for="candidate in candidates" :key="candidate.id" :value="String(candidate.id)">
              {{ candidate.first_name }} {{ candidate.last_name }} ({{ candidate.email }})
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Initial stage</span>
          <select
            v-model="createForm.stage"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option v-for="stage in stages" :key="stage.value" :value="stage.value">
              {{ stage.label }}
            </option>
          </select>
        </label>

        <div class="flex items-center justify-end gap-3">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeCreate"
          >
            Cancel
          </button>
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
            type="submit"
            :disabled="isCreating"
          >
            {{ isCreating ? 'Creating...' : 'Create application' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
