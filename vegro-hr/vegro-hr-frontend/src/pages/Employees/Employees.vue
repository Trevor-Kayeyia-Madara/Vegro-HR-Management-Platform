<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import employeeService from '../../services/employeeService';
import CreateEmployeeModal from '../../components/CreateEmployeeModal.vue';
import EditEmployeeModal from '../../components/EditEmployeeModal.vue';

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
      apiClient.get('/api/roles'),
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
};

const closeView = () => {
  isViewOpen.value = false;
  selectedEmployee.value = null;
};

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

onMounted(loadEmployees);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Employees</p>
          <h1 class="text-3xl font-semibold">Employee Directory</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Track employee records, departments, roles, and salary.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search employees..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openModal"
          >
            Add employee
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
                <th class="px-6 py-4 font-medium">Employee ID</th>
                <th class="px-6 py-4 font-medium">Name</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Email</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Department</th>
                <th class="px-6 py-4 font-medium hidden lg:table-cell">Role</th>
                <th class="px-6 py-4 font-medium hidden md:table-cell">Salary</th>
                <th class="px-6 py-4 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-if="isLoading">
                <td class="px-6 py-6 text-center text-slate-400" colspan="7">
                  Loading employees...
                </td>
              </tr>
              <tr
                v-for="employee in filteredEmployees"
                :key="employee.id"
                class="hover:bg-white/5"
              >
                <td class="px-6 py-4 font-medium text-slate-100">{{ employee.id }}</td>
                <td class="px-6 py-4 text-slate-200/80">{{ employee.name || '—' }}</td>
                <td class="px-6 py-4 text-slate-200/70 hidden md:table-cell">{{ employee.email }}</td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">{{ employee.department || '—' }}</td>
                <td class="px-6 py-4 text-slate-300/70 hidden lg:table-cell">
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
                <td class="px-6 py-4 text-slate-300/70 hidden md:table-cell">
                  {{ employee.salary ? Number(employee.salary).toLocaleString() : '—' }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openView(employee)"
                    >
                      View
                    </button>
                    <button
                      class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                      type="button"
                      @click="openEdit(employee)"
                    >
                      Edit
                    </button>
                    <button
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
                <td class="px-6 py-6 text-center text-slate-400" colspan="7">
                  No employees found yet.
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
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm"
        @click="closeView"
      ></div>
    </transition>

    <transition name="slide-up">
      <div v-if="isViewOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-slate-950 p-6 text-white shadow-[0_30px_90px_rgba(15,23,42,0.75)]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs uppercase tracking-[0.24em] text-emerald-200/80">Employee</p>
              <h2 class="text-2xl font-semibold">Profile Snapshot</h2>
            </div>
            <button
              class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200"
              type="button"
              @click="closeView"
            >
              Close
            </button>
          </div>
          <div class="mt-6 grid gap-4 text-sm">
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

