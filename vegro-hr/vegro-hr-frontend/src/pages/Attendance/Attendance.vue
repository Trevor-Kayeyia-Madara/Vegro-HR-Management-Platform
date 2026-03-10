<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'AttendancePage' });

const records = ref([]);
const employees = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activeRecord = ref(null);
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
  date: '',
  status: 'present',
});

const statuses = ['present', 'absent', 'late', 'excused'];
const { hasRole } = useAuth();
const canManageAttendance = computed(() => hasRole(['admin', 'hr']));

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

const loadAttendance = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [attendanceResponse, employeeResponse] = await Promise.all([
      apiClient.get('/api/attendances', {
        params: { page: currentPage.value, per_page: pageSize.value },
      }),
      apiClient.get('/api/employees', { params: { per_page: 1000 } }),
    ]);
    const parsed = parsePaginated(attendanceResponse);
    records.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    employees.value = unwrapList(employeeResponse);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load attendance.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activeRecord.value = null;
  form.value = {
    employee_id: '',
    date: new Date().toISOString().slice(0, 10),
    status: 'present',
  };
  isModalOpen.value = true;
};

const openEdit = (record) => {
  modalMode.value = 'edit';
  activeRecord.value = record;
  form.value = {
    employee_id: record?.employee_id || '',
    date: record?.date || '',
    status: record?.status || 'present',
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
      date: form.value.date,
      status: form.value.status,
    };

    if (modalMode.value === 'create') {
      await apiClient.post('/api/attendances', payload);
    } else if (activeRecord.value?.id) {
      await apiClient.put(`/api/attendances/${activeRecord.value.id}`, payload);
    }

    await loadAttendance();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save attendance.';
  } finally {
    isSubmitting.value = false;
  }
};

const deleteRecord = async (record) => {
  const confirmed = window.confirm('Delete this attendance record?');
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/attendances/${record.id}`);
    await loadAttendance();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete attendance.';
  }
};

const filteredRecords = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return records.value;
  return records.value.filter((record) => {
    const employeeName = record?.employee?.name?.toLowerCase() || '';
    const status = record?.status?.toLowerCase() || '';
    const date = record?.date || '';
    return employeeName.includes(query) || status.includes(query) || date.includes(query);
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadAttendance();
};

onMounted(loadAttendance);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Attendance</p>
          <h1 class="text-3xl font-semibold">Daily Attendance</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Track attendance status across your workforce.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search attendance..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            v-if="canManageAttendance"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add record
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
            <table class="min-w-200 w-full text-left text-xs sm:text-sm">
              <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
                <tr>
                  <th class="px-6 py-4 font-medium">Employee</th>
                  <th class="px-6 py-4 font-medium">Date</th>
                  <th class="px-6 py-4 font-medium">Status</th>
                  <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                    Loading attendance...
                  </td>
                </tr>
                <tr
                  v-for="record in filteredRecords"
                  :key="record.id"
                  class="hover:bg-white/5"
                >
                  <td class="px-6 py-4 text-slate-100">
                    {{ record.employee?.name || `Employee #${record.employee_id}` }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">{{ record.date }}</td>
                  <td class="px-6 py-4">
                    <span
                      class="rounded-full px-3 py-1 text-xs"
                      :class="record.status === 'present'
                        ? 'bg-emerald-400/10 text-emerald-200'
                        : record.status === 'late'
                          ? 'bg-amber-400/10 text-amber-200'
                          : record.status === 'excused'
                            ? 'bg-sky-400/10 text-sky-200'
                            : 'bg-rose-400/10 text-rose-200'"
                    >
                      {{ record.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        v-if="canManageAttendance"
                        class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openEdit(record)"
                      >
                        Edit
                      </button>
                      <button
                        v-if="canManageAttendance"
                        class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                        type="button"
                        @click="deleteRecord(record)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!isLoading && !filteredRecords.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="4">
                    No attendance records found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing {{ filteredRecords.length }} of {{ pagination.total }} records
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
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Attendance
              </p>
              <h2 class="text-2xl font-semibold">
                {{ modalMode === 'create' ? 'New Record' : 'Update Record' }}
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
              <span>Date</span>
              <input
                v-model="form.date"
                type="date"
                required
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
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
              {{ isSubmitting ? 'Saving...' : 'Save record' }}
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
