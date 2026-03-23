<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'ProjectsPage' });

const { hasPermission } = useAuth();
const canManage = computed(() => hasPermission('projects.manage'));

const projects = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

const unwrapList = (response) => {
  const payload = response?.data?.data ?? response?.data;
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload?.data?.data)) return payload.data.data;
  return [];
};

const loadProjects = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.get('/api/projects', { params: { per_page: 100 } });
    projects.value = unwrapList(response);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load projects.';
  } finally {
    isLoading.value = false;
  }
};

const isModalOpen = ref(false);
const modalMode = ref('create');
const activeProject = ref(null);
const nameInput = ref('');
const descriptionInput = ref('');
const statusInput = ref('active');
const isSubmitting = ref(false);

const openCreate = () => {
  modalMode.value = 'create';
  activeProject.value = null;
  nameInput.value = '';
  descriptionInput.value = '';
  statusInput.value = 'active';
  isModalOpen.value = true;
};

const openEdit = (project) => {
  modalMode.value = 'edit';
  activeProject.value = project;
  nameInput.value = project?.name || '';
  descriptionInput.value = project?.description || '';
  statusInput.value = project?.status || 'active';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitProject = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    const payload = {
      name: nameInput.value,
      description: descriptionInput.value || null,
      status: statusInput.value,
    };
    if (modalMode.value === 'create') {
      await apiClient.post('/api/projects', payload);
    } else if (activeProject.value?.id) {
      await apiClient.put(`/api/projects/${activeProject.value.id}`, payload);
    }
    await loadProjects();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save project.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteProject = async (project) => {
  const confirmed = window.confirm(`Delete ${project?.name || 'this project'}?`);
  if (!confirmed) return;
  try {
    await apiClient.delete(`/api/projects/${project.id}`);
    await loadProjects();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete project.';
  }
};

onMounted(loadProjects);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Projects</p>
          <h1 class="text-3xl font-semibold">Projects</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Track project roles and project reporting lines.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="loadProjects"
          >
            Refresh
          </button>
          <button
            v-if="canManage"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            New project
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
        <div class="overflow-x-auto">
          <table class="min-w-[720px] w-full text-left text-xs sm:text-sm">
            <thead class="bg-slate-950/60 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="px-6 py-4 font-medium">Name</th>
                <th class="px-6 py-4 font-medium">Status</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Description</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="4">Loading projects...</td>
              </tr>
              <tr v-for="project in projects" :key="project.id" class="hover:bg-white/5">
                <td class="px-6 py-4 font-medium text-slate-100">{{ project.name }}</td>
                <td class="px-6 py-4 text-slate-300/80">
                  <span
                    class="rounded-full border px-3 py-1 text-xs uppercase tracking-[0.2em]"
                    :class="project.status === 'archived' ? 'border-white/10 text-slate-400' : 'border-emerald-400/30 text-emerald-200'"
                  >
                    {{ project.status || 'active' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                  {{ project.description || '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="canManage"
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(project)"
                    >
                      Edit
                    </button>
                    <button
                      v-if="canManage"
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deleteProject(project)"
                    >
                      Delete
                    </button>
                    <span v-if="!canManage" class="text-slate-500">—</span>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !projects.length">
                <td class="px-6 py-6 text-center text-slate-400" colspan="4">No projects yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-if="isModalOpen"
      class="vegro-modal-viewport"
      @click.self="closeModal"
    >
      <div class="vegro-modal max-w-xl p-4 text-white shadow-[0_30px_120px_rgba(0,0,0,0.55)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
              {{ modalMode === 'create' ? 'Create' : 'Edit' }}
            </p>
            <h2 class="mt-2 text-2xl font-semibold">{{ modalMode === 'create' ? 'New project' : 'Project details' }}</h2>
          </div>
          <button
            class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
            type="button"
            @click="closeModal"
          >
            Close
          </button>
        </div>

        <form class="mt-6 max-h-[62dvh] overflow-y-auto pr-1 flex flex-col gap-4" @submit.prevent="submitProject">
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Name</span>
            <input
              v-model="nameInput"
              type="text"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>

          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Status</span>
            <select
              v-model="statusInput"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option value="active">Active</option>
              <option value="archived">Archived</option>
            </select>
          </label>

          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Description</span>
            <textarea
              v-model="descriptionInput"
              rows="4"
              class="rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>

          <button
            class="mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
            type="submit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Saving...' : 'Save project' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>


