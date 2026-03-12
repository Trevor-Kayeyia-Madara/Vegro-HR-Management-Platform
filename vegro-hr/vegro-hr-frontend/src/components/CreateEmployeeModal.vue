<script setup>
import { ref, watch } from 'vue';
import employeeService from '../services/EmployeeService';

defineOptions({ name: 'CreateEmployeeModal' });

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  departments: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);

const isSubmitting = ref(false);
const errorMessage = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  department_id: '',
  role_ids: [],
  salary: '',
  status: 'active',
});

const resetForm = () => {
  form.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    department_id: '',
    role_ids: [],
    salary: '',
    status: 'active',
  };
  errorMessage.value = '';
};

const closeModal = () => {
  emit('close');
};

const submitForm = async () => {
  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    await employeeService.createEmployee({
      ...form.value,
      department_id: Number(form.value.department_id),
      role_ids: form.value.role_ids.map((id) => Number(id)),
      salary: Number(form.value.salary),
      status: form.value.status,
    });
    emit('created');
    resetForm();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to create employee.';
  } finally {
    isSubmitting.value = false;
  }
};

watch(
  () => props.isOpen,
  (value) => {
    if (!value) resetForm();
  },
);
</script>

<template>
  <transition name="fade">
    <div
      v-if="isOpen"
      class="vegro-modal-overlay"
      @click="closeModal"
    ></div>
  </transition>

  <transition name="slide-up">
    <div v-if="isOpen" class="vegro-modal-wrap">
      <div class="vegro-modal">
        <div class="vegro-modal-header">
          <div>
            <p class="vegro-modal-title">Create Employee</p>
            <h2 class="vegro-modal-subtitle">New Employee</h2>
          </div>
          <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
        </div>

        <form class="vegro-modal-body grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>First name</span>
            <input
              v-model="form.first_name"
              type="text"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Last name</span>
            <input
              v-model="form.last_name"
              type="text"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
            <span>Email</span>
            <input
              v-model="form.email"
              type="email"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Phone</span>
            <input
              v-model="form.phone"
              type="text"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Salary</span>
            <input
              v-model="form.salary"
              type="number"
              min="0"
              step="0.01"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            />
          </label>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Department</span>
            <select
              v-model="form.department_id"
              required
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option value="" disabled>Select department</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">
                {{ department.name }}
              </option>
            </select>
          </label>
          <div class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Roles</span>
            <div class="flex flex-wrap gap-2 rounded-xl border border-white/10 bg-slate-950/40 p-3">
              <button
                v-for="role in roles"
                :key="role.id"
                type="button"
                class="rounded-full border px-3 py-1 text-xs font-medium transition"
                :class="form.role_ids.includes(role.id)
                  ? 'border-emerald-300/60 bg-emerald-300/20 text-emerald-100'
                  : 'border-white/10 bg-white/5 text-slate-200/80 hover:bg-white/10'"
                @click="() => {
                  if (form.role_ids.includes(role.id)) {
                    form.role_ids = form.role_ids.filter((id) => id !== role.id);
                  } else {
                    form.role_ids = [...form.role_ids, role.id];
                  }
                }"
              >
                {{ role.title || role.name }}
              </button>
            </div>
            <span class="text-xs text-slate-400">Select one or more roles.</span>
          </div>
          <label class="flex flex-col gap-2 text-sm text-slate-200/80">
            <span>Status</span>
            <select
              v-model="form.status"
              class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </label>

          <p v-if="errorMessage" class="sm:col-span-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs text-rose-100">
            {{ errorMessage }}
          </p>

          <button
            class="sm:col-span-2 mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
            type="submit"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Saving...' : 'Create employee' }}
          </button>
        </form>
      </div>
    </div>
  </transition>
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
