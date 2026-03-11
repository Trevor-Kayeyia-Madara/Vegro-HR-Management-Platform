<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'LeaveRequestsPage' });

const requests = ref([]);
const employees = ref([]);
const approvers = ref({
  manager: null,
  hr: [],
  directors: [],
});
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeRequest = ref(null);
const isSubmitting = ref(false);

const searchQuery = ref('');
const statusFilter = ref('all');
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
});

const { user, roleTitle, isAdmin, checkAuth, hasPermission } = useAuth();
const isEmployee = computed(() => roleTitle.value === 'employee');
const canApprove = computed(() => hasPermission('leaves.approve'));
const canManageRequests = computed(() => hasPermission('leaves.manage'));
const currentEmployeeId = ref(null);
const currentEmployee = computed(() =>
  employees.value.find((employee) => employee.id === currentEmployeeId.value),
);

const resolveCurrentEmployee = async () => {
  if (!user.value?.email) return null;
  try {
    const response = await apiClient.get(`/api/employees/email/${user.value.email}`);
    const employee = response?.data?.data || response?.data;
    currentEmployeeId.value = employee?.id || null;
    return currentEmployeeId.value;
  } catch (error) {
    return null;
  }
};

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
    if (!user.value) {
      await checkAuth();
    }

    if (isEmployee.value && !currentEmployeeId.value) {
      await resolveCurrentEmployee();
    }

    const endpoint = isEmployee.value && currentEmployeeId.value
      ? `/api/leave-requests/employee/${currentEmployeeId.value}`
      : '/api/leave-requests/all';

    const employeeResponsePromise = apiClient.get('/api/employees', { params: { per_page: 1000 } });
    const requestsResponsePromise = apiClient.get(endpoint, {
      params: { page: currentPage.value, per_page: pageSize.value },
    });
    const approverResponsePromise = apiClient.get('/api/leave-requests/approvers');

    const [requestsResponse, employeeResponse, approverResponse] = await Promise.all([
      requestsResponsePromise,
      employeeResponsePromise,
      approverResponsePromise,
    ]);
    const parsed = parsePaginated(requestsResponse);
    requests.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    employees.value = unwrapList(employeeResponse);
    approvers.value = approverResponse?.data?.data || approvers.value;
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
    employee_id: isEmployee.value ? currentEmployeeId.value || '' : '',
    start_date: '',
    end_date: '',
    reason: '',
  };
  isModalOpen.value = true;
};

const openEdit = (request) => {
  modalMode.value = 'edit';
  activeRequest.value = request;
  form.value = {
    employee_id: isEmployee.value ? currentEmployeeId.value || '' : request?.employee_id || '',
    start_date: request?.start_date || '',
    end_date: request?.end_date || '',
    reason: request?.reason || '',
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
  return requests.value.filter((request) => {
    const employeeName = request?.employee?.name?.toLowerCase() || '';
    const status = request?.status?.toLowerCase() || '';
    const matchesQuery = !query || employeeName.includes(query) || status.includes(query);
    const matchesStatus = statusFilter.value === 'all' || status === statusFilter.value;
    return matchesQuery && matchesStatus;
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
          <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs">
            <button
              class="rounded-full px-3 py-1 transition"
              :class="statusFilter === 'all' ? 'bg-emerald-300/20 text-emerald-200' : 'text-slate-300/70'"
              type="button"
              @click="statusFilter = 'all'"
            >
              All
            </button>
            <button
              class="rounded-full px-3 py-1 transition"
              :class="statusFilter === 'pending' ? 'bg-amber-300/20 text-amber-200' : 'text-slate-300/70'"
              type="button"
              @click="statusFilter = 'pending'"
            >
              Pending
            </button>
            <button
              class="rounded-full px-3 py-1 transition"
              :class="statusFilter === 'approved' ? 'bg-emerald-300/20 text-emerald-200' : 'text-slate-300/70'"
              type="button"
              @click="statusFilter = 'approved'"
            >
              Approved
            </button>
            <button
              class="rounded-full px-3 py-1 transition"
              :class="statusFilter === 'rejected' ? 'bg-rose-300/20 text-rose-200' : 'text-slate-300/70'"
              type="button"
              @click="statusFilter = 'rejected'"
            >
              Rejected
            </button>
          </div>
          <button
            v-if="isEmployee || canManageRequests"
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
            <table class="min-w-245 w-full text-left text-xs sm:text-sm">
              <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
                <tr>
                  <th class="px-6 py-4 font-medium">Employee</th>
                  <th class="px-6 py-4 font-medium">Start</th>
                  <th class="px-6 py-4 font-medium">End</th>
                  <th class="px-6 py-4 font-medium hidden lg:table-cell">Reason</th>
                  <th class="px-6 py-4 font-medium">Status</th>
                  <th class="px-6 py-4 font-medium hidden xl:table-cell">Approved by</th>
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
                  <td class="px-6 py-4 text-slate-300/80 hidden xl:table-cell">
                    <div v-if="request.approver" class="text-xs">
                      <p class="text-slate-100">{{ request.approver.name }}</p>
                      <p class="text-slate-400">{{ request.approved_role || 'Approver' }}</p>
                    </div>
                    <span v-else class="text-slate-400">—</span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        v-if="isEmployee || canManageRequests"
                        class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openEdit(request)"
                      >
                        Edit
                      </button>
                      <button
                        v-if="request.status === 'pending' && canApprove"
                        class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20"
                        type="button"
                        @click="updateStatus(request, 'approved')"
                      >
                        Approve
                      </button>
                      <button
                        v-if="request.status === 'pending' && canApprove"
                        class="rounded-full border border-amber-300/40 bg-amber-300/10 px-3 py-1 text-xs text-amber-200 transition hover:bg-amber-300/20"
                        type="button"
                        @click="updateStatus(request, 'rejected')"
                      >
                        Reject
                      </button>
                      <button
                        v-if="isEmployee || canManageRequests"
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
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Leave Request
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Request' : 'Update Request' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Employee</span>
              <select
                v-model="form.employee_id"
                required
                :disabled="isEmployee"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ employee.name || `Employee #${employee.id}` }}
                </option>
              </select>
              <span v-if="isEmployee" class="text-xs text-slate-400">
                Your employee profile is used automatically.
              </span>
            </label>
            <div
              v-if="isEmployee"
              class="sm:col-span-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs text-slate-300/80"
            >
              <span class="text-[11px] uppercase tracking-[0.24em] text-slate-400">Annual leave balance</span>
              <p class="mt-2 text-sm text-emerald-200">
                {{ currentEmployee?.annual_leave_balance ?? 0 }} days remaining
              </p>
              <p class="mt-1 text-xs text-slate-400">
                {{ currentEmployee?.annual_leave_used ?? 0 }} used / {{ currentEmployee?.annual_leave_days ?? 0 }} total
              </p>
            </div>
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

            <div class="sm:col-span-2 rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-xs text-slate-300/80">
              <p class="text-[11px] uppercase tracking-[0.24em] text-slate-400">Approval flow</p>
              <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-amber-300/40 bg-amber-300/10 px-3 py-1 text-amber-200">
                  Submitted
                </span>
                <span class="text-slate-500">→</span>
                <span class="rounded-full border border-blue-300/40 bg-blue-300/10 px-3 py-1 text-blue-200">
                  Department manager
                </span>
                <span class="text-slate-500">→</span>
                <span class="rounded-full border border-indigo-300/40 bg-indigo-300/10 px-3 py-1 text-indigo-200">
                  HR review
                </span>
                <span class="text-slate-500">→</span>
                <span class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-emerald-200">
                  Approved
                </span>
                <span class="text-slate-500">/</span>
                <span class="rounded-full border border-rose-400/40 bg-rose-400/10 px-3 py-1 text-rose-200">
                  Rejected
                </span>
              </div>
              <div class="mt-4 grid gap-2 text-xs text-slate-300/70">
                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                  <span class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Manager</span>
                  <p class="mt-1 text-slate-200">
                    {{ approvers.manager?.name || 'Not assigned' }}
                    <span v-if="approvers.manager?.email" class="text-slate-400">({{ approvers.manager.email }})</span>
                  </p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                  <span class="text-[11px] uppercase tracking-[0.2em] text-slate-400">HR</span>
                  <p v-if="approvers.hr?.length" class="mt-1 text-slate-200">
                    {{ approvers.hr.map((person) => person.name).join(', ') }}
                  </p>
                  <p v-else class="mt-1 text-slate-400">No HR approvers assigned.</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 px-3 py-2">
                  <span class="text-[11px] uppercase tracking-[0.2em] text-slate-400">Director / MD</span>
                  <p v-if="approvers.directors?.length" class="mt-1 text-slate-200">
                    {{ approvers.directors.map((person) => person.name).join(', ') }}
                  </p>
                  <p v-else class="mt-1 text-slate-400">No director or MD assigned.</p>
                </div>
              </div>
            </div>

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
