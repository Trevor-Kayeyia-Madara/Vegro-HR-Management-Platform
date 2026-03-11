<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'PayslipsPage' });

const payslips = ref([]);
const payrolls = ref([]);
const isLoading = ref(true);
const errorMessage = ref('');
const isModalOpen = ref(false);
const modalMode = ref('create');
const activePayslip = ref(null);
const isSubmitting = ref(false);
const isExporting = ref(false);
const { hasPermission, hasRole } = useAuth();

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
  payroll_id: '',
  pdf_path: '',
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

const normalizeEmployeeName = (employee) => {
  if (!employee) return '';
  if (employee.name) return employee.name;
  const parts = [employee.first_name, employee.last_name].filter(Boolean);
  return parts.join(' ');
};

const loadPayslips = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const isEmployee = hasRole(['employee']);
    const payslipsResponse = await apiClient.get(isEmployee ? '/api/payslips/me' : '/api/payslips', {
      params: { page: currentPage.value, per_page: pageSize.value },
    });

    const parsed = parsePaginated(payslipsResponse);
    payslips.value = parsed.items;
    pagination.value = parsed.meta;
    currentPage.value = parsed.meta.current_page;

    if (!isEmployee) {
      const payrollsResponse = await apiClient.get('/api/payrolls', { params: { per_page: 1000 } });
      payrolls.value = unwrapList(payrollsResponse);
    } else {
      payrolls.value = [];
    }
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load payslips.';
  } finally {
    isLoading.value = false;
  }
};

const openCreate = () => {
  modalMode.value = 'create';
  activePayslip.value = null;
  form.value = {
    payroll_id: '',
    pdf_path: '',
  };
  isModalOpen.value = true;
};

const openEdit = (payslip) => {
  modalMode.value = 'edit';
  activePayslip.value = payslip;
  form.value = {
    payroll_id: payslip?.payroll_id || '',
    pdf_path: payslip?.pdf_path || '',
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
    if (modalMode.value === 'create') {
      await apiClient.post('/api/payslips', {
        payroll_id: Number(form.value.payroll_id),
        pdf_path: form.value.pdf_path || null,
      });
    } else if (activePayslip.value?.id) {
      await apiClient.put(`/api/payslips/${activePayslip.value.id}`, {
        pdf_path: form.value.pdf_path || null,
      });
    }

    await loadPayslips();
    closeModal();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to save payslip.';
  } finally {
    isSubmitting.value = false;
  }
};

const approvePayslip = async (payslip) => {
  if (!payslip?.id) return;
  try {
    await apiClient.post(`/api/payslips/${payslip.id}/approve`);
    await loadPayslips();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to approve payslip.';
  }
};

const issuePayslip = async (payslip) => {
  if (!payslip?.id) return;
  try {
    await apiClient.post(`/api/payslips/${payslip.id}/issue`);
    await loadPayslips();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to issue payslip.';
  }
};

const deletePayslip = async (payslip) => {
  const confirmed = window.confirm('Delete this payslip?');
  if (!confirmed) return;

  try {
    await apiClient.delete(`/api/payslips/${payslip.id}`);
    await loadPayslips();
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to delete payslip.';
  }
};

const exportCsv = async () => {
  isExporting.value = true;
  errorMessage.value = '';

  try {
    const response = await apiClient.get('/api/payslips/export/csv', {
      responseType: 'blob',
    });
    const blob = new Blob([response.data], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `payslips-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to export payslips.';
  } finally {
    isExporting.value = false;
  }
};

const filteredPayslips = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return payslips.value;
  return payslips.value.filter((payslip) => {
    const employeeName = (
      normalizeEmployeeName(payslip?.payroll?.employee) || payslip?.employee_name || ''
    ).toLowerCase();
    const payrollPeriod = `${payslip?.payroll?.month || ''} ${payslip?.payroll?.year || ''}`
      .trim()
      .toLowerCase();
    const pdfPath = payslip?.pdf_path?.toLowerCase() || '';
    const status = String(payslip?.status || '').toLowerCase();
    return (
      employeeName.includes(query) ||
      payrollPeriod.includes(query) ||
      pdfPath.includes(query) ||
      status.includes(query)
    );
  });
});

const totalPages = computed(() => pagination.value.last_page || 1);
const canManage = computed(() => hasPermission('payslips.manage'));

const goToPage = (page) => {
  const nextPage = Math.min(Math.max(page, 1), totalPages.value);
  if (nextPage === currentPage.value) return;
  currentPage.value = nextPage;
  loadPayslips();
};

onMounted(loadPayslips);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Payslips</p>
          <h1 class="text-3xl font-semibold">Payslip Library</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Generate, store, and export employee payslips.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search payslips..."
            class="h-10 rounded-full border border-white/10 bg-white/5 px-4 text-xs text-slate-200 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
          />
          <button
            class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
            type="button"
            :disabled="isExporting"
            @click="exportCsv"
          >
            {{ isExporting ? 'Exporting...' : 'Export CSV' }}
          </button>
          <button
            v-if="canManage"
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
            type="button"
            @click="openCreate"
          >
            Add payslip
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
                  <th class="px-6 py-4 font-medium">Period</th>
                  <th class="px-6 py-4 font-medium">Gross</th>
                  <th class="px-6 py-4 font-medium">Deductions</th>
                  <th class="px-6 py-4 font-medium">Net Pay</th>
                  <th class="px-6 py-4 font-medium hidden lg:table-cell">Status</th>
                  <th class="px-6 py-4 font-medium hidden xl:table-cell">PDF Path</th>
                  <th class="px-6 py-4 font-medium text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-if="isLoading">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="8">
                    Loading payslips...
                  </td>
                </tr>
                <tr
                  v-for="payslip in filteredPayslips"
                  :key="payslip.id"
                  class="hover:bg-white/5"
                >
                  <td class="px-6 py-4 text-slate-100">
                    {{ normalizeEmployeeName(payslip.payroll?.employee) || payslip.employee_name || `Payroll #${payslip.payroll_id}` }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ payslip.payroll?.month || '-' }} {{ payslip.payroll?.year || '' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ payslip.gross_pay ?? payslip.payroll?.gross_salary ?? '-' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ payslip.total_deductions ?? '-' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ payslip.net_pay ?? payslip.payroll?.net_salary ?? '-' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80 hidden lg:table-cell">
                    <span
                      class="rounded-full border border-white/10 px-3 py-1 text-xs uppercase tracking-[0.18em]"
                      :class="payslip.status === 'approved'
                        ? 'border-emerald-300/40 text-emerald-200'
                        : payslip.status === 'issued'
                          ? 'border-blue-300/40 text-blue-200'
                          : 'border-white/20 text-slate-300/70'"
                    >
                      {{ payslip.status || 'draft' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-300/80 hidden xl:table-cell">
                    {{ payslip.pdf_path || '-' }}
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        v-if="canManage && payslip.status === 'draft'"
                        class="rounded-full border border-amber-300/40 bg-amber-300/10 px-3 py-1 text-xs text-amber-200 transition hover:bg-amber-300/20"
                        type="button"
                        @click="approvePayslip(payslip)"
                      >
                        Approve
                      </button>
                      <button
                        v-if="canManage && payslip.status === 'approved'"
                        class="rounded-full border border-sky-300/40 bg-sky-300/10 px-3 py-1 text-xs text-sky-200 transition hover:bg-sky-300/20"
                        type="button"
                        @click="issuePayslip(payslip)"
                      >
                        Issue
                      </button>
                      <button
                        v-if="canManage"
                        class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-200 transition hover:bg-white/10"
                        type="button"
                        @click="openEdit(payslip)"
                      >
                        Edit
                      </button>
                      <button
                        v-if="canManage"
                        class="rounded-full border border-rose-500/30 bg-rose-500/10 px-3 py-1 text-xs text-rose-200 transition hover:bg-rose-500/20"
                        type="button"
                        @click="deletePayslip(payslip)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!isLoading && !filteredPayslips.length">
                  <td class="px-6 py-6 text-center text-slate-400" colspan="8">
                    No payslips found.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-300/70">
        <span>
          Showing {{ filteredPayslips.length }} of {{ pagination.total }} payslips
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
                {{ modalMode === 'create' ? 'Create' : 'Edit' }} Payslip
              </p>
              <h2 class="vegro-modal-subtitle">
                {{ modalMode === 'create' ? 'New Payslip' : 'Update Payslip' }}
              </h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeModal">Close</button>
          </div>

          <form class="vegro-modal-body grid gap-4 sm:grid-cols-2" @submit.prevent="submitForm">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Payroll</span>
              <select
                v-model="form.payroll_id"
                required
                :disabled="modalMode === 'edit'"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="" disabled>Select payroll</option>
                <option v-for="payroll in payrolls" :key="payroll.id" :value="payroll.id">
                  {{ normalizeEmployeeName(payroll.employee) || `Payroll #${payroll.id}` }}
                  - {{ payroll.month || '-' }} {{ payroll.year || '' }}
                  - Net {{ payroll.net_salary ?? '-' }}
                </option>
              </select>
              <span class="text-xs text-slate-400">
                Payslip values are auto-calculated from the linked payroll run.
              </span>
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>PDF path</span>
              <input
                v-model="form.pdf_path"
                type="text"
                placeholder="payslips/payslip-1.pdf"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>

            <button
              class="sm:col-span-2 mt-2 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
              type="submit"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Saving...' : 'Save payslip' }}
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
