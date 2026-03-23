<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';
import recruitmentService from '../../services/recruitmentService';

defineOptions({ name: 'JobPostingsPanel' });

const { hasPermission } = useAuth();
const canManage = computed(() => hasPermission('recruitment.manage'));

const jobs = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

const departments = ref([]);
const employees = ref([]);

const statusFilter = ref('');
const meta = ref({ total: 0 });

const jobStatuses = [
  { value: 'draft', label: 'Draft' },
  { value: 'open', label: 'Open' },
  { value: 'closed', label: 'Closed' },
];

const employmentTypes = [
  { value: 'full_time', label: 'Full time' },
  { value: 'part_time', label: 'Part time' },
  { value: 'contract', label: 'Contract' },
  { value: 'intern', label: 'Intern' },
];

const normalize = (value) =>
  String(value || '')
    .toLowerCase()
    .replace(/[\s-_]/g, '');

const managerOptions = computed(() =>
  employees.value
    .filter((employee) => {
      const roles = Array.isArray(employee?.roles) ? employee.roles : [];
      const primary = normalize(employee?.role);
      const hasManagerRole = primary === 'manager' || roles.some((role) => normalize(role) === 'manager');
      return hasManagerRole && employee?.user_id;
    })
    .map((employee) => ({
      id: Number(employee.user_id),
      name: employee.name,
      email: employee.email,
    }))
    .sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''))),
);

const loadJobs = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await recruitmentService.getJobs({
      per_page: 25,
      ...(statusFilter.value ? { status: statusFilter.value } : {}),
    });
    const parsed = recruitmentService.parsePaginated(response);
    jobs.value = parsed.items;
    meta.value = parsed.meta;
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load job postings.';
  } finally {
    isLoading.value = false;
  }
};

const loadLookups = async () => {
  if (!canManage.value) return;
  try {
    const [deptResponse, employeesResponse] = await Promise.all([
      apiClient.get('/api/departments', { params: { per_page: 1000 } }),
      apiClient.get('/api/employees', { params: { per_page: 2000 } }),
    ]);
    departments.value = recruitmentService.parsePaginated(deptResponse).items;
    employees.value = recruitmentService.parsePaginated(employeesResponse).items;
  } catch {
    departments.value = [];
    employees.value = [];
  }
};

const isModalOpen = ref(false);
const isSubmitting = ref(false);
const formError = ref('');

const form = ref({
  title: '',
  status: 'draft',
  department_id: '',
  hiring_manager_user_id: '',
  employment_type: '',
  location: '',
  openings: '',
  description: '',
});

const openCreate = async () => {
  if (!canManage.value) return;
  form.value = {
    title: '',
    status: 'draft',
    department_id: '',
    hiring_manager_user_id: '',
    employment_type: '',
    location: '',
    openings: '',
    description: '',
  };
  formError.value = '';
  await loadLookups();
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const toNumberOrNull = (value) => {
  if (value === '' || value === null || typeof value === 'undefined') return null;
  const n = Number(value);
  return Number.isFinite(n) ? n : null;
};

const submitJob = async () => {
  if (!canManage.value) return;
  isSubmitting.value = true;
  formError.value = '';

  try {
    await recruitmentService.createJob({
      title: form.value.title,
      status: form.value.status || null,
      department_id: toNumberOrNull(form.value.department_id),
      hiring_manager_user_id: toNumberOrNull(form.value.hiring_manager_user_id),
      employment_type: form.value.employment_type || null,
      location: form.value.location || null,
      openings: toNumberOrNull(form.value.openings),
      description: form.value.description || null,
    });
    closeModal();
    await loadJobs();
  } catch (error) {
    formError.value = error?.response?.data?.message || 'Unable to create job posting.';
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  await Promise.all([loadJobs(), loadLookups()]);
});
</script>

<template>
  <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <label class="text-xs text-slate-300/80">
          <span class="mr-2 uppercase tracking-[0.2em] text-slate-400">Status</span>
          <select
            v-model="statusFilter"
            class="h-10 rounded-2xl border border-white/10 bg-slate-950/30 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70"
            @change="loadJobs"
          >
            <option value="">All</option>
            <option v-for="status in jobStatuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>
      </div>

      <div class="flex items-center gap-2">
        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="loadJobs"
        >
          Refresh
        </button>
        <button
          v-if="canManage"
          class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          type="button"
          @click="openCreate"
        >
          New job
        </button>
      </div>
    </div>

    <p
      v-if="errorMessage"
      class="mt-4 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
    >
      {{ errorMessage }}
    </p>

    <div v-if="isLoading" class="mt-6 text-sm text-slate-300/80">Loading job postings...</div>

    <div v-else class="mt-6 overflow-hidden rounded-3xl border border-white/10 bg-slate-950/30">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-slate-200">
          <thead class="bg-white/5 text-xs uppercase tracking-[0.2em] text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">Title</th>
              <th class="px-4 py-3 text-left font-semibold">Department</th>
              <th class="px-4 py-3 text-left font-semibold">Hiring manager</th>
              <th class="px-4 py-3 text-left font-semibold">Status</th>
              <th class="px-4 py-3 text-left font-semibold">Openings</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr v-for="job in jobs" :key="job.id" class="hover:bg-white/5">
              <td class="px-4 py-3">
                <div class="font-medium text-white">{{ job.title }}</div>
                <div class="text-xs text-slate-400/80">{{ job.location || '—' }}</div>
              </td>
              <td class="px-4 py-3 text-slate-300">{{ job?.department?.name || '—' }}</td>
              <td class="px-4 py-3 text-slate-300">{{ job?.hiring_manager?.name || '—' }}</td>
              <td class="px-4 py-3 text-slate-300">{{ job.status || '—' }}</td>
              <td class="px-4 py-3 text-slate-300">{{ job.openings || '—' }}</td>
            </tr>
            <tr v-if="!jobs.length">
              <td class="px-4 py-6 text-center text-slate-400" colspan="5">No job postings yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p class="mt-4 text-xs text-slate-400/80">
      Showing {{ jobs.length }} of {{ meta.total }} job postings.
    </p>
  </section>

  <div
    v-if="isModalOpen"
    class="vegro-modal-viewport"
    @click.self="closeModal"
  >
    <div class="vegro-modal max-w-3xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Create job</p>
          <h2 class="mt-2 text-2xl font-semibold">{{ form.title || 'Job posting' }}</h2>
          <p class="mt-1 text-sm text-slate-300/70">Set the basics now; fill in details later.</p>
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

      <form class="mt-6 max-h-[62dvh] overflow-y-auto pr-1 grid gap-4 md:grid-cols-2" @submit.prevent="submitJob">
        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Title</span>
          <input
            v-model="form.title"
            required
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Status</span>
          <select
            v-model="form.status"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option v-for="status in jobStatuses" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Employment type</span>
          <select
            v-model="form.employment_type"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option value="">—</option>
            <option v-for="type in employmentTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Department</span>
          <select
            v-model="form.department_id"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option value="">—</option>
            <option v-for="dept in departments" :key="dept.id" :value="String(dept.id)">
              {{ dept.name }}
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Hiring manager</span>
          <select
            v-model="form.hiring_manager_user_id"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70"
          >
            <option value="">—</option>
            <option v-for="manager in managerOptions" :key="manager.id" :value="String(manager.id)">
              {{ manager.name }}{{ manager.email ? ` (${manager.email})` : '' }}
            </option>
          </select>
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Location</span>
          <input
            v-model="form.location"
            type="text"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Openings</span>
          <input
            v-model="form.openings"
            type="number"
            min="1"
            step="1"
            class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
        </label>

        <label class="block text-xs text-slate-300/80 md:col-span-2">
          <span class="mb-2 block uppercase tracking-[0.2em] text-slate-400">Description</span>
          <textarea
            v-model="form.description"
            rows="4"
            class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          ></textarea>
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
            {{ isSubmitting ? 'Saving...' : 'Create job' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
