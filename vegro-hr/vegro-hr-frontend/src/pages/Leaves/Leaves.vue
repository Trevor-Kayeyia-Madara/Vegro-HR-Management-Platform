<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'LeaveRequestsPage' });

const requests = ref([]);
const employees = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeRequest = ref(null);
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
  employee_id: '',
  start_date: '',
  end_date: '',
  reason: '',
  status: 'pending',
});

const statuses = ['pending', 'approved', 'rejected'];

const unwrapList = (response) => {
  if (Array.isArray(response?.data)) return response.data;
  const data = response?.data?.data;
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
};

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

const loadRequests = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [requestsResponse, employeeResponse] = await Promise.all([
      apiClient.get('/api/leave-requests/all', {
        params: { page: currentPage.value, per_page: pageSize.value },
      }),
      apiClient.get('/api/employees', { params: { per_page: 1000 } }),
    ]);
    const parsed = parsePaginated(requestsResponse);
    requests.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    employees.value = unwrapList(employeeResponse);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load leave requests.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activeRequest.value = null;
  form.value = {
    employee_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    status: 'pending',
  };
  isModalOpen.value = true;
};

const openEdit = (request) => {
  modalMode.value = 'edit';
  activeRequest.value = request;
  form.value = {
    employee_id: request?.employee_id || '',
    start_date: request?.start_date || '',
    end_date: request?.end_date || '',
    reason: request?.reason || '',
    status: request?.status || 'pending',
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
    const payload = {
      employee_id: Number(form.value.employee_id),
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      reason: form.value.reason,
      status: form.value.status,
    };

    if (modalMode.value === 'create') {
      await apiClient.post('/api/leave-requests', payload);
    } else if (activeRequest.value?.id) {
      await apiClient.put(`/api/leave-requests/${activeRequest.value.id}`, payload);
    }

    await loadRequests();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save leave request.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteRequest = async (request) => {
  const confirmed = window.confirm('Delete this leave request?');
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/leave-requests/${request.id}`);
    await loadRequests();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete leave request.';
  }
};

const updateStatus = async (request, status) => {
  try {
    if (status === 'approved') {
      await apiClient.post(`/api/leave-requests/${request.id}/approve`);
    } else if (status === 'rejected') {
      await apiClient.post(`/api/leave-requests/${request.id}/reject`);
    }
    await loadRequests();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to update leave status.';
  }
};

const filteredRequests = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return requests.value;
  return requests.value.filter((request) => {
    const employeeName = request?.employee?.name?.toLowerCase() || '';
    const status = request?.status?.toLowerCase() || '';
    return employeeName.includes(query) || status.includes(query);
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadRequests();
};

onMounted(loadRequests);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Leave Requests</p>
          <h1 class="text-3xl font-semibold">Leave Requests</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Review and approve employee leave submissions.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search leave..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add request
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
        <div class="max-h-130 overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-225 w-full text-left text-xs sm:text-sm">
              <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
                <tr>
                  <th class="px-6 py-4 font-medium">Employee</th>
                  <th class="px-6 py-4 font-medium">Start</th>
                  <th class="px-6 py-4 font-medium">End</th>
                  <th class="px-6 py-4 font-medium hidden lg:table-cell">Reason</th>
                  <th class="px-6 py-4 font-medium">Status</th>
                  <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="6">
                    Loading leave requests...
                  </td>
                </tr>
                <tr
                  v-for="request in filteredRequests"
                  :key="request.id"
                  class="hover:bg-white/5"
                >
                  <td class="px-6 py-4 text-slate-100">
                    {{ request.employee?.name || `Employee #${request.employee_id}` }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">{{ request.start_date }}</td>
                  <td class="px-6 py-4 text-slate-300/80">{{ request.end_date }}</td>
                  <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                    {{ request.reason || '—' }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="rounded-full px-3 py-1 text-xs"
                      :class="request.status === 'approved'
                        ? 'bg-emerald-400/10 text-emerald-200'
                        : request.status === 'rejected'
                          ? 'bg-rose-400/10 text-rose-200'
                          : 'bg-amber-400/10 text-amber-200'"
                    >
                      {{ request.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openEdit(request)"
                      >
                        Edit
                      </button>
                      <button
                        v-if="request.status === 'pending'"
                        class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20"
                        type="button"
                        @click="updateStatus(request, 'approved')"
                      >
                        Approve
                      </button>
                      <button
                        v-if="request.status === 'pending'"
                        class="rounded-full border border-amber-300/40 bg-amber-300/10 px-3 py-1 text-xs text-amber-200 transition hover:bg-amber-300/20"
                        type="button"
                        @click="updateStatus(request, 'rejected')"
                      >
                        Reject
                      </button>
                      <button
                        class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                        type="button"
                        @click="deleteRequest(request)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!isLoading && !filteredRequests.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="6">
                    No leave requests found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing {{ filteredRequests.length }} of {{ pagination.total }} requests
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
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm"
        @click="closeModal"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="w-full max-w-xl rounded-3xl border border-white/10 bg-slate-950 p-6 text-white shadow-[0_30px_90px_rgba(15,23,42,0.75)]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Leave Request
              </p>
              <h2 class="text-2xl font-semibold">
                {{ modalMode === 'create' ? 'New Request' : 'Update Request' }}
              </h2>
            </div>
            <button
              class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200"
              type="button"
              @click="closeModal"
            >
              Close
            </button>
          </div>

          <form class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Employee</span>
              <select
                v-model="form.employee_id"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name || `Employee #${employee.id}` }}
                </option>
              </select>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Start date</span>
              <input
                v-model="form.start_date"
                type="date"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>End date</span>
              <input
                v-model="form.end_date"
                type="date"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Reason</span>
              <textarea
                v-model="form.reason"
                rows="3"
                class="rounded-xl border border-white/10 bg-slate-950/40 px-4 py-2 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              ></textarea>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Status</span>
              <select
                v-model="form.status"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option v-for="status in statuses" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </label>

            <button
              class="sm:col-span-2 mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save request' }}
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
