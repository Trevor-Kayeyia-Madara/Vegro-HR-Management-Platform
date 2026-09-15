<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';
import useAuth from '../../hooks/useAuth';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

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
const isViewModalOpen = ref(false);
const selectedPayslip = ref(null);
const { hasPermission, hasRole, user } = useAuth();

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

const monthLabel = (value) => {
  const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
  ];
  return months[Number(value) - 1] || '—';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  });
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
const canViewAll = computed(() => hasRole(['admin', 'hr', 'finance', 'manager', 'director', 'md']));
const isEmployee = computed(() => hasRole(['employee']));

const formatCurrency = (value) =>
  new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'KES',
    maximumFractionDigits: 2,
  }).format(value || 0);

const viewPayslip = (payslip) => {
  selectedPayslip.value = payslip;
  isViewModalOpen.value = true;
};

const closeViewModal = () => {
  isViewModalOpen.value = false;
  selectedPayslip.value = null;
};

const downloadPayslip = async (payslip) => {
  try {
    const doc = new jsPDF('p', 'mm', 'a4');
    const payroll = payslip.payroll || {};
    
    const pageWidth = 210;
    const margin = 15;
    
    // Header
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.text('PAYSLIP', margin, 20);
    
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(`Date: ${new Date().toLocaleDateString()}`, pageWidth - margin, 20, { align: 'right' });
    
    // Line
    doc.setDrawColor(0, 0, 0);
    doc.setLineWidth(0.5);
    doc.line(margin, 25, pageWidth - margin, 25);
    
    // Employee info
    let y = 35;
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Employee: ' + (payslip.employee_name || 'N/A'), margin, y);
    
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    y += 8;
    doc.text('ID: ' + (payslip.employee_number || 'N/A'), margin, y);
    y += 8;
    doc.text('Email: ' + (payslip.employee_email || 'N/A'), margin, y);
    
    // Period
    y += 15;
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Period: ' + formatDate(payslip.pay_period_start) + ' to ' + formatDate(payslip.pay_period_end), margin, y);
    
    // Earnings
    y += 15;
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('EARNINGS', margin, y);
    
    y += 10;
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text('Basic Salary: ' + formatCurrency(payslip.earnings_breakdown?.basic_salary || payroll?.basic_salary || 0), margin, y);
    y += 8;
    doc.text('Allowances: ' + formatCurrency(payslip.earnings_breakdown?.allowances || payroll?.allowances || 0), margin, y);
    
    if (payroll?.overtime_allowance) {
      y += 8;
      doc.text('Overtime Allowance: ' + formatCurrency(payroll.overtime_allowance), margin, y);
    }
    
    y += 8;
    doc.setFont('helvetica', 'bold');
    doc.text('Gross Pay: ' + formatCurrency(payslip.gross_pay || payroll?.gross_salary || 0), margin, y);
    
    // Deductions
    y += 15;
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('DEDUCTIONS', margin, y);
    
    y += 10;
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text('NSSF: ' + formatCurrency(payslip.deductions_breakdown?.statutory?.nssf || 0), margin, y);
    y += 8;
    doc.text('SHIF: ' + formatCurrency(payslip.deductions_breakdown?.statutory?.shif || 0), margin, y);
    y += 8;
    doc.text('Housing Levy: ' + formatCurrency(payslip.deductions_breakdown?.statutory?.housing_levy || 0), margin, y);
    y += 8;
    doc.text('PAYE: ' + formatCurrency(payslip.deductions_breakdown?.statutory?.paye || 0), margin, y);
    
    y += 8;
    doc.setFont('helvetica', 'bold');
    doc.text('Total Deductions: ' + formatCurrency(payslip.total_deductions || 0), margin, y);
    
    // Net Pay
    y += 15;
    doc.setLineWidth(1);
    doc.line(margin, y, pageWidth - margin, y);
    
    y += 12;
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('NET PAY: ' + formatCurrency(payslip.net_pay || payroll?.net_salary || 0), margin, y);
    
    // Footer
    y += 20;
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    doc.text('Generated by Vegro HR Management Platform', margin, y);
    
    doc.save(`payslip-${payslip.id}-${payslip.employee_name?.replace(/\s+/g, '-').toLowerCase() || 'employee'}.pdf`);
    
  } catch (error) {
    console.error('PDF generation error:', error);
    errorMessage.value = 'Unable to generate PDF payslip.';
  }
};

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
        <div class="max-h-[72vh] overflow-auto">
          <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-left text-xs sm:text-sm">
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
                    {{ payslip.payroll?.month ? monthLabel(payslip.payroll.month) : '-' }} {{ payslip.payroll?.year || '' }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ formatCurrency(payslip.gross_pay ?? payslip.payroll?.gross_salary ?? 0) }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ formatCurrency(payslip.total_deductions ?? 0) }}
                  </td>
                  <td class="px-6 py-4 text-slate-300/80">
                    {{ formatCurrency(payslip.net_pay ?? payslip.payroll?.net_salary ?? 0) }}
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
                        class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200 transition hover:bg-emerald-300/20"
                        type="button"
                        @click="viewPayslip(payslip)"
                      >
                        View
                      </button>
                      <button
                        class="rounded-full border border-blue-300/40 bg-blue-300/10 px-3 py-1 text-xs text-blue-200 transition hover:bg-blue-300/20"
                        type="button"
                        @click="downloadPayslip(payslip)"
                      >
                        Download
                      </button>
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

    <!-- View Payslip Modal -->
    <transition name="slide-up">
      <div v-if="isViewModalOpen" class="vegro-modal-wrap">
        <div class="vegro-modal max-w-2xl">
          <div class="vegro-modal-header">
            <div>
              <p class="vegro-modal-title">Payslip Details</p>
              <h2 class="vegro-modal-subtitle">{{ selectedPayslip?.employee_name || 'Employee' }}</h2>
            </div>
            <button class="vegro-modal-close" type="button" @click="closeViewModal">Close</button>
          </div>

          <div class="vegro-modal-body space-y-4" v-if="selectedPayslip">
            <div class="grid gap-4 rounded-2xl border border-white/10 bg-white/5 p-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Employee</p>
                <p class="mt-1 text-sm text-slate-200">{{ selectedPayslip.employee_name || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Employee Number</p>
                <p class="mt-1 text-sm text-slate-200">{{ selectedPayslip.employee_number || 'N/A' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Period</p>
                <p class="mt-1 text-sm text-slate-200">{{ formatDate(selectedPayslip.pay_period_start) }} to {{ formatDate(selectedPayslip.pay_period_end) }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-[0.26em] text-slate-400">Status</p>
                <p class="mt-1 text-sm text-slate-200 capitalize">{{ selectedPayslip.status || 'draft' }}</p>
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-400 mb-3">Earnings</p>
              <div class="space-y-2" v-if="selectedPayslip.earnings_breakdown">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">Basic Salary</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.earnings_breakdown.basic_salary || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">Allowances</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.earnings_breakdown.allowances || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm border-l-2 border-emerald-300/30 pl-3 bg-emerald-300/5 py-2" v-if="selectedPayslip.payroll?.overtime_allowance">
                  <span class="text-slate-300/70">Overtime Allowance</span>
                  <span class="text-emerald-200">{{ formatCurrency(selectedPayslip.payroll?.overtime_allowance || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-white/10 pt-2">
                  <span class="text-slate-200">Gross Pay</span>
                  <span class="text-emerald-200">{{ formatCurrency(selectedPayslip.gross_pay || 0) }}</span>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4" v-if="selectedPayslip.payroll">
              <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-400 mb-3">Rate Calculations</p>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-300/70">Daily Rate</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.payroll?.daily_rate || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-300/70">Hourly Rate</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.payroll?.hourly_rate || 0) }}</span>
                </div>
                <div class="flex justify-between" v-if="selectedPayslip.payroll?.overtime_rate">
                  <span class="text-slate-300/70">Overtime Rate (1.5x)</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.payroll?.overtime_rate || 0) }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-2 border-t border-white/10 pt-2">
                  Calculated based on 30.33 days/month and 12 hours/day
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
              <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-400 mb-3">Deductions</p>
              <div class="space-y-2" v-if="selectedPayslip.deductions_breakdown">
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">NSSF</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.deductions_breakdown.statutory?.nssf || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">SHIF</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.deductions_breakdown.statutory?.shif || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">Housing Levy</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.deductions_breakdown.statutory?.housing_levy || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-slate-300/70">PAYE</span>
                  <span class="text-slate-200">{{ formatCurrency(selectedPayslip.deductions_breakdown.statutory?.paye || 0) }}</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-white/10 pt-2">
                  <span class="text-slate-200">Total Deductions</span>
                  <span class="text-rose-200">{{ formatCurrency(selectedPayslip.total_deductions || 0) }}</span>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/5 p-4" v-if="selectedPayslip.payroll">
              <p class="text-xs font-semibold uppercase tracking-[0.26em] text-slate-400 mb-3">Calculation Methodology</p>
              <div class="space-y-3 text-sm">
                <div class="bg-slate-900/50 rounded-lg p-3">
                  <p class="text-xs font-semibold text-slate-300 mb-2">Overtime Calculation (if applicable)</p>
                  <div class="space-y-1 text-xs text-slate-400">
                    <div class="flex justify-between">
                      <span>1. Daily Rate</span>
                      <span>{{ formatCurrency(selectedPayslip.payroll?.basic_salary || 0) }} ÷ 30.33 = {{ formatCurrency(selectedPayslip.payroll?.daily_rate || 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>2. Hourly Rate</span>
                      <span>{{ formatCurrency(selectedPayslip.payroll?.daily_rate || 0) }} ÷ 12 = {{ formatCurrency(selectedPayslip.payroll?.hourly_rate || 0) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>3. Overtime Rate</span>
                      <span>{{ formatCurrency(selectedPayslip.payroll?.hourly_rate || 0) }} × 1.5 = {{ formatCurrency(selectedPayslip.payroll?.overtime_rate || 0) }}</span>
                    </div>
                    <div class="flex justify-between" v-if="selectedPayslip.payroll?.overtime_allowance">
                      <span>4. Overtime Allowance</span>
                      <span>{{ formatCurrency(selectedPayslip.payroll?.overtime_allowance || 0) }}</span>
                    </div>
                  </div>
                </div>
                <div class="bg-slate-900/50 rounded-lg p-3">
                  <p class="text-xs font-semibold text-slate-300 mb-2">Statutory Deductions (Kenya)</p>
                  <div class="space-y-1 text-xs text-slate-400">
                    <div class="flex justify-between">
                      <span>NSSF</span>
                      <span>6% of gross salary (capped at KES 6,480)</span>
                    </div>
                    <div class="flex justify-between">
                      <span>SHIF</span>
                      <span>2.75% of gross salary (min KES 300)</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Housing Levy</span>
                      <span>1.5% of gross salary</span>
                    </div>
                    <div class="flex justify-between">
                      <span>PAYE</span>
                      <span>Progressive tax on taxable income</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/10 p-4">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-xs uppercase tracking-[0.26em] text-emerald-400">Net Pay</p>
                  <p class="mt-1 text-2xl font-bold text-emerald-200">{{ formatCurrency(selectedPayslip.net_pay || 0) }}</p>
                </div>
                <button
                  class="rounded-full border border-emerald-300/40 bg-emerald-300/20 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/30"
                  type="button"
                  @click="downloadPayslip(selectedPayslip)"
                >
                  Download
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
