<script setup>
import { computed, onMounted, ref } from 'vue';
import LeaveService from '../../services/LeaveService';
import useAuth from '../../hooks/useAuth';
import { formatDateRange } from '../../utils/dateFormat';

defineOptions({ name: 'LeaveRequestsPage' });

const { hasRole, hasPermission } = useAuth();
const canConfigure = computed(() => hasRole(['admin', 'hr']));
const canManage = computed(() => hasPermission('leaves.manage'));
const canApprove = computed(() => hasPermission('leaves.approve'));
const canRequestLeave = computed(() => hasRole(['admin', 'hr', 'manager', 'employee', 'director', 'md']));

const loading = ref(false);
const error = ref('');
const items = ref([]);
const leaveTypes = ref([]);
const isRequestSubmitting = ref(false);
const requestSuccess = ref('');
const requestForm = ref({
  type: 'annual',
  start_date: '',
  end_date: '',
  reason: '',
});
const defaultLeaveTypeOptions = [
  { type: 'annual', label: 'Annual Leave' },
  { type: 'maternity', label: 'Maternity Leave' },
  { type: 'paternity', label: 'Paternity Leave' },
  { type: 'sick', label: 'Sick Leave' },
  { type: 'adoptive', label: 'Adoptive Leave' },
  { type: 'compassionate', label: 'Compassionate Leave' },
  { type: 'emergency', label: 'Emergency Leave' },
];
const requestLeaveOptions = computed(() => {
  if (leaveTypes.value.length) {
    return leaveTypes.value.map((type) => ({
      type: type.type,
      label: type.label,
    }));
  }

  return defaultLeaveTypeOptions;
});

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const data = await LeaveService.getRequests('/api/leave-requests', { per_page: 50 });
    items.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);

    if (canConfigure.value) {
      const types = await LeaveService.getLeaveTypes();
      leaveTypes.value = Array.isArray(types) ? types : [];
    }
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load leave data';
  } finally {
    loading.value = false;
  }
};

const setStatus = async (item, status) => {
  try {
    if (status === 'approved') await LeaveService.approveRequest(item.id);
    if (status === 'rejected') await LeaveService.rejectRequest(item.id);
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to update leave status';
  }
};

const submitRequest = async () => {
  requestSuccess.value = '';
  error.value = '';
  isRequestSubmitting.value = true;

  try {
    await LeaveService.createRequest({
      type: requestForm.value.type,
      start_date: requestForm.value.start_date,
      end_date: requestForm.value.end_date,
      reason: requestForm.value.reason,
    });
    requestSuccess.value = 'Leave request submitted successfully.';
    requestForm.value = {
      type: requestForm.value.type || 'annual',
      start_date: '',
      end_date: '',
      reason: '',
    };
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to submit leave request';
  } finally {
    isRequestSubmitting.value = false;
  }
};

onMounted(load);
</script>

<template>
  <section class="min-h-full bg-slate-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <header class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold sm:text-3xl">Leaves</h1>
        <p class="text-sm text-slate-300/80">Leave requests and policy overview.</p>
      </header>

      <p v-if="error" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
        {{ error }}
      </p>

      <article
        v-if="canRequestLeave"
        class="rounded-2xl border border-white/10 bg-white/5 p-4"
      >
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Request Leave</h2>
        <p
          v-if="requestSuccess"
          class="mt-3 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-xs text-emerald-100"
        >
          {{ requestSuccess }}
        </p>
        <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="submitRequest">
          <label class="flex flex-col gap-1 text-xs text-slate-300/80">
            Leave Type
            <select
              v-model="requestForm.type"
              class="rounded-lg border border-white/15 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none"
              required
            >
              <option v-for="type in requestLeaveOptions" :key="type.type" :value="type.type">
                {{ type.label }}
              </option>
            </select>
          </label>
          <label class="flex flex-col gap-1 text-xs text-slate-300/80">
            Start Date
            <input
              v-model="requestForm.start_date"
              type="date"
              class="rounded-lg border border-white/15 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none"
              required
            />
          </label>
          <label class="flex flex-col gap-1 text-xs text-slate-300/80">
            End Date
            <input
              v-model="requestForm.end_date"
              type="date"
              class="rounded-lg border border-white/15 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none"
              required
            />
          </label>
          <label class="flex flex-col gap-1 text-xs text-slate-300/80 md:col-span-2">
            Reason
            <textarea
              v-model="requestForm.reason"
              rows="3"
              class="rounded-lg border border-white/15 bg-slate-900/70 px-3 py-2 text-sm text-white outline-none"
              placeholder="Optional reason"
            />
          </label>
          <div class="md:col-span-2">
            <button
              type="submit"
              class="rounded-full border border-emerald-300/40 px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-100 transition hover:border-emerald-200/60 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isRequestSubmitting"
            >
              {{ isRequestSubmitting ? 'Submitting...' : 'Submit Leave Request' }}
            </button>
          </div>
        </form>
      </article>

      <article
        v-if="canConfigure"
        class="rounded-2xl border border-white/10 bg-white/5 p-4"
      >
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Leave Type Settings</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="type in leaveTypes"
            :key="type.id || type.type"
            class="rounded-xl border border-white/10 bg-slate-900/50 p-3"
          >
            <p class="font-medium text-white">{{ type.label }}</p>
            <p class="mt-1 text-xs text-slate-300/80">Type: {{ type.type }}</p>
            <p class="text-xs text-slate-300/80">Days/year: {{ type.days_per_year ?? 0 }}</p>
            <p class="text-xs text-slate-300/80">Min service: {{ type.min_months_of_service ?? 0 }} months</p>
          </div>
        </div>
      </article>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-3 sm:p-4">
        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="grid gap-3 md:hidden">
          <div v-for="item in items" :key="`m-${item.id}`" class="rounded-xl border border-white/10 bg-slate-900/50 p-3">
            <p class="font-medium">{{ item.employee?.name || `Employee #${item.employee_id}` }}</p>
            <p class="text-xs text-slate-300/80">{{ item.type || 'annual' }} • {{ formatDateRange(item.start_date, item.end_date) }}</p>
            <p class="mt-1 text-xs capitalize text-slate-300/80">Status: {{ item.status }}</p>
            <div v-if="canApprove && item.status === 'pending'" class="mt-2 flex gap-2">
              <button class="rounded-full border border-emerald-300/40 px-3 py-1 text-xs text-emerald-200" @click="setStatus(item, 'approved')">Approve</button>
              <button class="rounded-full border border-amber-300/40 px-3 py-1 text-xs text-amber-200" @click="setStatus(item, 'rejected')">Reject</button>
            </div>
          </div>
        </div>
        <div v-if="!loading" class="hidden overflow-x-auto md:block">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Employee</th>
                <th class="px-3 py-2">Type</th>
                <th class="px-3 py-2">Dates</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="border-t border-white/10">
                <td class="px-3 py-2">{{ item.employee?.name || `Employee #${item.employee_id}` }}</td>
                <td class="px-3 py-2 capitalize">{{ item.type || 'annual' }}</td>
                <td class="px-3 py-2 text-slate-300/80">{{ formatDateRange(item.start_date, item.end_date) }}</td>
                <td class="px-3 py-2 capitalize">{{ item.status }}</td>
                <td class="px-3 py-2 text-right">
                  <div v-if="canApprove && item.status === 'pending'" class="inline-flex gap-2">
                    <button class="rounded-full border border-emerald-300/40 px-3 py-1 text-xs text-emerald-200" @click="setStatus(item, 'approved')">Approve</button>
                    <button class="rounded-full border border-amber-300/40 px-3 py-1 text-xs text-amber-200" @click="setStatus(item, 'rejected')">Reject</button>
                  </div>
                  <span v-else class="text-xs text-slate-500">{{ canManage ? '-' : 'View only' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>


