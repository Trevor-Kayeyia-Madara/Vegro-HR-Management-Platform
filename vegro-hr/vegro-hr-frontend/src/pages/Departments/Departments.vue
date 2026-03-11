<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'Org UnitsPage' });

const departments = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeDepartment = ref(null);
const nameInput = ref('');
const descriptionInput = ref('');
const isSubmitting = ref(false);
const searchQuery = ref('');
const pageSize = ref(8);
const currentPage = ref(1);
const { hasPermission } = useAuth();
const canManageDepartments = computed(() => hasPermission('departments.manage'));
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: pageSize.value,
  total: 0,
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

const loadDepartments = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/departments', {
      params: { page: currentPage.value, per_page: pageSize.value },
    });
    const parsed = parsePaginated(response);
    departments.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load departments.';
  } finally {
    isLoading.value = false;
  }
};

const filteredDepartments = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return departments.value;
  return departments.value.filter((department) =>
    department?.name?.toLowerCase().includes(query),
  );
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadDepartments();
};

const openCreate = () => {
  modalMode.value = 'create';
  activeDepartment.value = null;
  nameInput.value = '';
  descriptionInput.value = '';
  isModalOpen.value = true;
};

const openEdit = (department) => {
  modalMode.value = 'edit';
  activeDepartment.value = department;
  nameInput.value = department?.name || '';
  descriptionInput.value = department?.description || '';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    if (modalMode.value === 'create') {
      await apiClient.post('/api/departments', {
        name: nameInput.value,
        description: descriptionInput.value,
      });
    } else if (activeDepartment.value?.id) {
      await apiClient.put(`/api/departments/${activeDepartment.value.id}`, {
        name: nameInput.value,
        description: descriptionInput.value,
      });
    }
    await loadDepartments();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save department.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteDepartment = async (department) => {
  const confirmed = window.confirm(`Delete ${department?.name || 'this department'}?`);
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/departments/${department.id}`);
    await loadDepartments();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete department.';
  }
};

onMounted(loadDepartments);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Departments</p>
          <h1 class="text-3xl font-semibold">Departments</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Create, edit, and manage enterprise business units.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search departments..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            v-if="canManageDepartments"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add unit
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
        <div class="max-h-150 overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-250 w-full text-left text-xs sm:text-sm">
            <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="px-6 py-4 font-medium">ID</th>
                <th class="px-6 py-4 font-medium">Name</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Description</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                  Loading departments...
                </td>
              </tr>
              <tr
                v-for="department in filteredDepartments"
                :key="department.id"
                class="hover:bg-white/5"
              >
                <td class="px-6 py-4 font-medium text-slate-100">{{ department.id }}</td>
                <td class="px-6 py-4 text-slate-200/80">{{ department.name }}</td>
                <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                  {{ department.description || 'â€”' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="canManageDepartments"
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(department)"
                    >
                      Edit
                    </button>
                    <button
                      v-if="canManageDepartments"
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deleteDepartment(department)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredDepartments.length">
                <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                  No units found yet.
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
          {{ filteredDepartments.length }}
          of
          {{ pagination.total }}
          departments
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
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Department
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Unit' : 'Update Unit' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body flex flex-col gap-4" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Department name</span>
              <input
                v-model="nameInput"
                type="text"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Description</span>
              <textarea
                v-model="descriptionInput"
                rows="3"
                class="rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              ></textarea>
            </label>

            <button
              class="mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save unit' }}
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

