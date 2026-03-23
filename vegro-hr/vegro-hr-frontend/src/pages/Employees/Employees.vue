<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import employeeService from '../../services/employeeService';
import CreateEmployeeModal from '../../components/CreateEmployeeModal.vue';
import EditEmployeeModal from '../../components/EditEmployeeModal.vue';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'EmployeesPage' });

const employees = ref([]);
const departments = ref([]);
const roles = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');

const isModalOpen = ref(false);
const isEditOpen = ref(false);
const isViewOpen = ref(false);
const selectedEmployee = ref(null);
const searchQuery = ref('');
const pageSize = ref(8);
const currentPage = ref(1);
const { hasPermission, hasRole } = useAuth();
const canManageEmployees = computed(
  () => hasRole(['admin', 'hr']) && hasPermission('employees.manage'),
);
const canManageHierarchy = computed(() => hasRole(['admin', 'hr']));
const canCreateLeaveForEmployee = computed(() => hasRole(['admin', 'hr']) && hasPermission('leaves.manage'));
const employeeCsvInput = ref(null);
const csvMode = ref('upsert');
const isCsvBusy = ref(false);
const isSyncingLeaveDefaults = ref(false);
const isHierarchyLoading = ref(false);
const isHierarchySaving = ref(false);
const hierarchyForm = ref({
  functional_manager_ids: [],
  dotted_manager_ids: [],
});
const isLeaveSubmitting = ref(false);
const leaveSuccessMessage = ref('');
const leaveForm = ref({
  type: 'annual',
  start_date: '',
  end_date: '',
  reason: '',
});
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: pageSize.value,
  total: 0,
});

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

const loadEmployees = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const [employeesResponse, departmentsResponse, rolesResponse] = await Promise.all([
      employeeService.getEmployees({ page: currentPage.value, per_page: pageSize.value }),
      apiClient.get('/api/departments', { params: { per_page: 500 } }),
      apiClient.get('/api/roles/assignable'),
    ]);

    const parsed = parsePaginated(employeesResponse);
    employees.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;
    departments.value = unwrapList(departmentsResponse);
    roles.value = unwrapList(rolesResponse);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load employees.';
  } finally {
    isLoading.value = false;
  }
};

const openModal = () => {
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const handleCreated = () => {
  loadEmployees();
};

const openEdit = (employee) => {
  selectedEmployee.value = employee;
  isEditOpen.value = true;
};

const closeEdit = () => {
  isEditOpen.value = false;
  selectedEmployee.value = null;
};

const openView = (employee) => {
  selectedEmployee.value = employee;
  isViewOpen.value = true;
  leaveSuccessMessage.value = '';
  leaveForm.value = {
    type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
  };
  if (canManageHierarchy.value) {
    loadHierarchy(employee);
  }
};

const closeView = () => {
  isViewOpen.value = false;
  selectedEmployee.value = null;
  leaveSuccessMessage.value = '';
  hierarchyForm.value = {
    functional_manager_ids: [],
    dotted_manager_ids: [],
  };
  leaveForm.value = {
    type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
  };
};

const loadHierarchy = async (employee) => {
  if (!employee?.id) return;
  isHierarchyLoading.value = true;
  try {
    const response = await employeeService.getEmployeeManagers(employee.id);
    const assignments = unwrapList(response);
    hierarchyForm.value = {
      functional_manager_ids: assignments
        .filter((item) => item?.relationship_type === 'functional')
        .map((item) => item?.manager?.id)
        .filter(Boolean),
      dotted_manager_ids: assignments
        .filter((item) => item?.relationship_type === 'dotted')
        .map((item) => item?.manager?.id)
        .filter(Boolean),
    };
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load reporting hierarchy.';
  } finally {
    isHierarchyLoading.value = false;
  }
};

const saveHierarchy = async () => {
  if (!selectedEmployee.value?.id) return;
  isHierarchySaving.value = true;
  try {
    await employeeService.syncEmployeeManagers(selectedEmployee.value.id, hierarchyForm.value);
    await loadHierarchy(selectedEmployee.value);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save hierarchy.';
  } finally {
    isHierarchySaving.value = false;
  }
};

const syncLeaveDefaults = async () => {
  isSyncingLeaveDefaults.value = true;
  errorMessage.value = '';
  try {
    await employeeService.syncLeaveBalancesDefaults();
    await loadEmployees();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to sync leave defaults.';
  } finally {
    isSyncingLeaveDefaults.value = false;
  }
};

const managerCandidates = computed(() =>
  employees.value.filter((employee) => employee?.user_id && employee?.id !== selectedEmployee.value?.id),
);

const leaveTypeLabelMap = {
  annual: 'Annual',
  sick: 'Sick',
  maternity: 'Maternity',
  paternity: 'Paternity',
  adoptive: 'Adoptive',
  compassionate: 'Compassionate',
  emergency: 'Emergency',
  public_holiday: 'Public holiday',
};

const leaveLabel = (type) => leaveTypeLabelMap[String(type || '').toLowerCase()] || String(type || 'Leave');

const handleUpdated = () => {
  loadEmployees();
  closeEdit();
};

const deleteEmployee = async (employee) => {
  const confirmed = window.confirm(`Delete ${employee?.name || 'this employee'}?`);
  if (!confirmed) return;

  try {
    await employeeService.deleteEmployee(employee.id);
    await loadEmployees();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete employee.';
  }
};

const filteredEmployees = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return employees.value;
  return employees.value.filter((employee) => {
    const name = employee?.name?.toLowerCase() || '';
    const email = employee?.email?.toLowerCase() || '';
    const department = employee?.department?.toLowerCase() || '';
    const role = employee?.role?.toLowerCase() || '';
    return (
      name.includes(query) ||
      email.includes(query) ||
      department.includes(query) ||
      role.includes(query)
    );
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadEmployees();
};

const submitLeaveForEmployee = async () => {
  if (!selectedEmployee.value?.id) return;
  isLeaveSubmitting.value = true;
  errorMessage.value = '';
  leaveSuccessMessage.value = '';
  try {
    await apiClient.post('/api/leave-requests', {
      employee_id: selectedEmployee.value.id,
      type: leaveForm.value.type,
      start_date: leaveForm.value.start_date,
      end_date: leaveForm.value.end_date,
      reason: leaveForm.value.reason || null,
    });
    leaveSuccessMessage.value = 'Leave request added successfully.';
    leaveForm.value = {
      type: 'annual',
      start_date: '',
      end_date: '',
      reason: '',
    };
    await loadEmployees();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to add leave request.';
  } finally {
    isLeaveSubmitting.value = false;
  }
};

const downloadCsv = async () => {
  isCsvBusy.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.get('/api/employees/export/csv', { responseType: 'blob' });
    const url = window.URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'employees.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to export employees.';
  } finally {
    isCsvBusy.value = false;
  }
};

const triggerCsvUpload = () => {
  if (employeeCsvInput.value) {
    employeeCsvInput.value.value = '';
    employeeCsvInput.value.click();
  }
};

const handleCsvSelected = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  isCsvBusy.value = true;
  errorMessage.value = '';
  try {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('mode', csvMode.value);
    await apiClient.post('/api/employees/import/csv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    await loadEmployees();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to import employees.';
  } finally {
    isCsvBusy.value = false;
  }
};

onMounted(loadEmployees);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Employees</p>
          <h1 class="text-3xl font-semibold">Workforce Directory</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Track employee records, departments, and workforce roles in one secure directory.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search employees..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <div v-if="canManageEmployees" class="flex items-center gap-2">
            <select
              v-model="csvMode"
              class="h-10 rounded-full border border-white/10 bg-white/5 px-3 text-xs text-slate-200 outline-none"
            >
              <option value="upsert">Upsert</option>
              <option value="skip">Skip existing</option>
            </select>
            <input
              ref="employeeCsvInput"
              type="file"
              accept=".csv,text/csv"
              class="hidden"
              @change="handleCsvSelected"
            />
            <button
              class="rounded-full border border-white/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10 disabled:opacity-60"
              type="button"
              :disabled="isCsvBusy"
              @click="downloadCsv"
            >
              Export CSV
            </button>
            <button
              class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
              type="button"
              :disabled="isCsvBusy"
              @click="triggerCsvUpload"
            >
              Import CSV
            </button>
            <button
              class="rounded-full border border-sky-300/40 bg-sky-300/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-sky-200 transition hover:bg-sky-300/20 disabled:opacity-60"
              type="button"
              :disabled="isSyncingLeaveDefaults"
              @click="syncLeaveDefaults"
            >
              {{ isSyncingLeaveDefaults ? 'Syncing...' : 'Sync Leave Defaults' }}
            </button>
          </div>
          <button
            v-if="canManageEmployees"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openModal"
          >
            Add member
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
        <div class="employees-table-scroll max-h-[72vh] w-full overflow-y-auto overflow-x-scroll overscroll-contain touch-pan-x">
            <table class="min-w-[920px] w-full table-fixed text-left text-[11px] sm:text-xs">
            <thead class="sticky top-0 bg-slate-950/90 text-xs uppercase tracking-[0.24em] text-slate-400">
              <tr>
                <th class="w-[70px] px-2 py-2 font-medium">ID</th>
                <th class="w-[150px] px-2 py-2 font-medium">Name</th>
                <th class="w-[190px] px-2 py-2 font-medium hidden md:table-cell">Email</th>
                <th class="w-[130px] px-2 py-2 font-medium hidden lg:table-cell">Department</th>
                <th class="w-[140px] px-2 py-2 font-medium hidden lg:table-cell">Role</th>
                <th class="w-[220px] px-2 py-2 font-medium hidden xl:table-cell">Leave Balances</th>
                <th class="w-[110px] px-2 py-2 font-medium hidden md:table-cell">Salary</th>
                <th class="w-[170px] px-2 py-2 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-3 py-5 text-center text-slate-400" colspan="8">
                  Loading employees...
                </td>
              </tr>
              <tr
                v-for="employee in filteredEmployees"
                :key="employee.id"
                class="hover:bg-white/5"
              >
                <td class="px-2 py-2 font-medium text-slate-100">{{ employee.id }}</td>
                <td class="px-2 py-2 text-slate-200/80 truncate" :title="employee.name || '—'">{{ employee.name || '—' }}</td>
                <td class="px-2 py-2 text-slate-200/70 hidden md:table-cell truncate" :title="employee.email">{{ employee.email }}</td>
                <td class="px-2 py-2 text-slate-300/70 hidden lg:table-cell truncate" :title="employee.department || '—'">{{ employee.department || '—' }}</td>
                <td class="px-2 py-2 text-slate-300/70 hidden lg:table-cell">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="role in employee.roles || (employee.role ? [employee.role] : [])"
                      :key="role"
                      class="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-200/80"
                    >
                      {{ role }}
                    </span>
                    <span v-if="!(employee.roles || employee.role)" class="text-slate-400">—</span>
                  </div>
                </td>
                                <td class="px-2 py-2 text-slate-300/70 hidden xl:table-cell">
                  <div class="space-y-2">
                    <p class="text-[11px] text-slate-200/80">
                      Annual:
                      {{ Number(employee.annual_leave_balance ?? 0).toFixed(1) }}
                      /
                      {{ Number(employee.annual_leave_days ?? 0).toFixed(1) }}
                    </p>
                    <div class="flex flex-wrap gap-1">
                      <span
                        v-for="balance in (employee.leave_balances || []).slice(0, 3)"
                        :key="`${employee.id}-${balance.leave_type}`"
                        class="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-[10px] text-slate-200/80"
                      >
                        {{ leaveLabel(balance.leave_type) }} {{ Number(balance.balance_days || 0).toFixed(1) }}
                      </span>
                    </div>
                  </div>
                </td><td class="px-2 py-2 text-slate-300/70 hidden md:table-cell">
                  {{ employee.salary ? Number(employee.salary).toLocaleString() : '—' }}
                </td>
                <td class="px-2 py-2">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openView(employee)"
                    >
                      View
                    </button>
                    <button
                      v-if="canManageEmployees"
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(employee)"
                    >
                      Edit
                    </button>
                    <button
                      v-if="canManageEmployees"
                      class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                      type="button"
                      @click="deleteEmployee(employee)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!isLoading && !filteredEmployees.length">
                <td class="px-3 py-5 text-center text-slate-400" colspan="8">
                  No team members found yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing
          {{ filteredEmployees.length }}
          of
          {{ pagination.total }}
          employees
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

    <CreateEmployeeModal
      :is-open="isModalOpen"
      :departments="departments"
      :roles="roles"
      @close="closeModal"
      @created="handleCreated"
    />

    <EditEmployeeModal
      :is-open="isEditOpen"
      :employee="selectedEmployee"
      :departments="departments"
      :roles="roles"
      @close="closeEdit"
      @updated="handleUpdated"
    />

    <transition name="fade">
      <div
        v-if="isViewOpen"
        class="vegro-modal-overlay"
        @click="closeView"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isViewOpen" class="vegro-modal-wrap">
        <div class="vegro-modal">
          <div class="vegro-modal-header">
            <div>
              <p class="vegro-modal-title">Employee</p>
              <h2 class="vegro-modal-subtitle">Profile Snapshot</h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeView">Close</button>
          </div>
          <div class="vegro-modal-body grid gap-4 text-sm">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Name</p>
              <p class="mt-2 text-lg font-semibold">{{ selectedEmployee?.name || '—' }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Email</p>
                <p class="mt-2 font-semibold">{{ selectedEmployee?.email || '—' }}</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Phone</p>
                <p class="mt-2 font-semibold">{{ selectedEmployee?.phone || '—' }}</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Department</p>
                <p class="mt-2 font-semibold">{{ selectedEmployee?.department || '—' }}</p>
              </div>
              <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Role</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span
                    v-for="role in selectedEmployee?.roles || (selectedEmployee?.role ? [selectedEmployee.role] : [])"
                    :key="role"
                    class="rounded-full border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-200/80"
                  >
                    {{ role }}
                  </span>
                  <span
                    v-if="!(selectedEmployee?.roles || selectedEmployee?.role)"
                    class="text-slate-400"
                  >
                    —
                  </span>
                </div>
              </div>
              <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Salary</p>
                <p class="mt-2 font-semibold">
                  {{ selectedEmployee?.salary ? Number(selectedEmployee.salary).toLocaleString() : '—' }}
                </p>
              </div>
            </div>

            <div
              v-if="canCreateLeaveForEmployee"
              class="rounded-2xl border border-white/10 bg-white/5 p-4"
            >
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Leave</p>
              <h3 class="mt-1 text-lg font-semibold">Add Leave Request</h3>

              <p
                v-if="leaveSuccessMessage"
                class="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100"
              >
                {{ leaveSuccessMessage }}
              </p>

              <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-xs text-slate-300/80">
                  <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Type</span>
                  <select
                    v-model="leaveForm.type"
                    class="h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none"
                  >
                    <option value="annual">Annual</option>
                    <option value="sick">Sick</option>
                    <option value="maternity">Maternity</option>
                    <option value="paternity">Paternity</option>
                    <option value="adoptive">Adoptive</option>
                    <option value="compassionate">Compassionate</option>
                    <option value="emergency">Emergency</option>
                  </select>
                </label>

                <label class="text-xs text-slate-300/80">
                  <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Start Date</span>
                  <input
                    v-model="leaveForm.start_date"
                    type="date"
                    class="h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none"
                  />
                </label>

                <label class="text-xs text-slate-300/80">
                  <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">End Date</span>
                  <input
                    v-model="leaveForm.end_date"
                    type="date"
                    class="h-10 w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm text-white outline-none"
                  />
                </label>

                <label class="text-xs text-slate-300/80 sm:col-span-2">
                  <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Reason</span>
                  <textarea
                    v-model="leaveForm.reason"
                    rows="2"
                    class="w-full rounded-xl border border-white/10 bg-slate-950/40 px-3 py-2 text-sm text-white outline-none"
                    placeholder="Optional reason"
                  />
                </label>
              </div>

              <div class="mt-4 flex justify-end">
                <button
                  class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
                  type="button"
                  :disabled="isLeaveSubmitting"
                  @click="submitLeaveForEmployee"
                >
                  {{ isLeaveSubmitting ? 'Adding...' : 'Add Leave' }}
                </button>
              </div>
            </div>
          </div>
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

<style scoped>
.employees-table-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.8) rgba(15, 23, 42, 0.45);
}

.employees-table-scroll::-webkit-scrollbar {
  height: 12px;
  width: 10px;
}

.employees-table-scroll::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.45);
}

.employees-table-scroll::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.8);
  border-radius: 9999px;
  border: 2px solid rgba(15, 23, 42, 0.45);
}
</style>

