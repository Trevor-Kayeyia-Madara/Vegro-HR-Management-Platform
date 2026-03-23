<script setup>
import { computed, onMounted, ref } from 'vue';
import useAuth from '../../hooks/useAuth';
import apiClient from '../../api/apiClient';
import onboardingService from '../../services/onboardingService';
import { formatDate, formatDateTime } from '../../utils/dateFormat';

defineOptions({ name: 'OnboardingDocumentsPage' });

const { hasPermission } = useAuth();

const canManage = computed(() => hasPermission('onboarding.manage'));
const canSign = computed(() => hasPermission('onboarding.sign'));

const loading = ref(false);
const error = ref('');
const success = ref('');

const templates = ref([]);
const assignments = ref([]);
const myAssignments = ref([]);
const employees = ref([]);

const templateForm = ref({
  title: '',
  type: 'contract',
  content: '',
  document: null,
  requires_signature: true,
});

const assignForm = ref({
  employee_id: '',
  template_id: '',
  due_date: '',
});

const signatureNames = ref({});

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    if (canManage.value) {
      const [tplResponse, assignmentResponse, employeeResponse] = await Promise.all([
        onboardingService.getTemplates({ per_page: 100 }),
        onboardingService.getAssignments({ per_page: 100 }),
        apiClient.get('/api/employees', { params: { per_page: 1000 } }),
      ]);
      templates.value = onboardingService.parsePaginated(tplResponse).items;
      assignments.value = onboardingService.parsePaginated(assignmentResponse).items;
      const employeePayload = employeeResponse?.data?.data;
      employees.value = employeePayload?.data || employeePayload || [];
    }

    const myResponse = await onboardingService.getMyAssignments({ per_page: 100 });
    myAssignments.value = onboardingService.parsePaginated(myResponse).items;
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load Drive data.';
  } finally {
    loading.value = false;
  }
};

const createTemplate = async () => {
  if (!templateForm.value.title || !templateForm.value.document) {
    error.value = 'Document title and file are required.';
    return;
  }
  error.value = '';
  success.value = '';
  try {
    await onboardingService.createTemplate(templateForm.value);
    templateForm.value = { title: '', type: 'contract', content: '', document: null, requires_signature: true };
    success.value = 'Document uploaded.';
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to upload document.';
  }
};

const handleDocumentSelected = (event) => {
  templateForm.value.document = event?.target?.files?.[0] || null;
};

const downloadTemplateFile = async (template) => {
  if (!template?.id) return;
  error.value = '';
  try {
    const response = await onboardingService.downloadTemplate(template.id);
    const blobUrl = window.URL.createObjectURL(response.data);
    const anchor = document.createElement('a');
    anchor.href = blobUrl;
    anchor.download = template.file_name || `${template.title || 'document'}.pdf`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(blobUrl);
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to download document.';
  }
};

const assign = async () => {
  if (!assignForm.value.employee_id || !assignForm.value.template_id) {
    error.value = 'Select employee and template.';
    return;
  }
  error.value = '';
  success.value = '';
  try {
    await onboardingService.assignDocument(assignForm.value);
    assignForm.value = { employee_id: '', template_id: '', due_date: '' };
    success.value = 'Document assigned.';
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to assign document.';
  }
};

const sign = async (item) => {
  const signatureName = (signatureNames.value[item.id] || '').trim();
  if (!signatureName) {
    error.value = 'Please enter your signature name.';
    return;
  }
  error.value = '';
  success.value = '';
  try {
    await onboardingService.signAssignment(item.id, { signature_name: signatureName });
    success.value = 'Document signed successfully.';
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to sign document.';
  }
};

onMounted(load);
</script>

<template>
  <section class="min-h-full bg-slate-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <header class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold sm:text-3xl">Drive</h1>
        <p class="text-sm text-slate-300/80">Digital documents hub for contracts, forms, and online signatures.</p>
      </header>

      <p v-if="error" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">{{ error }}</p>
      <p v-if="success" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ success }}</p>

      <article v-if="canManage" class="rounded-2xl border border-white/10 bg-white/5 p-4 sm:p-6">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Upload document</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <input v-model="templateForm.title" class="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none" placeholder="Document title" />
          <input v-model="templateForm.type" class="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none" placeholder="Type (contract, policy, NDA...)" />
          <input type="file" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" class="sm:col-span-2 h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none file:mr-4 file:rounded-full file:border-0 file:bg-emerald-300/20 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-200" @change="handleDocumentSelected" />
          <textarea v-model="templateForm.content" rows="3" class="sm:col-span-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm outline-none" placeholder="Optional notes/instructions for signers" />
        </div>
        <div class="mt-3 flex items-center justify-between">
          <label class="inline-flex items-center gap-2 text-xs text-slate-300">
            <input v-model="templateForm.requires_signature" type="checkbox" class="h-4 w-4 accent-emerald-400" />
            Requires signature
          </label>
          <button class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200" @click="createTemplate">Upload file</button>
        </div>
      </article>

      <article v-if="canManage" class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Assign document</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <select v-model="assignForm.employee_id" class="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none">
            <option value="">Select employee</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
              {{ employee.name || employee.first_name }} ({{ employee.email }})
            </option>
          </select>
          <select v-model="assignForm.template_id" class="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none">
            <option value="">Select document</option>
            <option v-for="template in templates" :key="template.id" :value="template.id">{{ template.title }}</option>
          </select>
          <input v-model="assignForm.due_date" type="date" class="h-11 rounded-2xl border border-white/10 bg-white/5 px-4 text-sm outline-none" />
        </div>
        <div class="mt-3 flex justify-end">
          <button class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200" @click="assign">Assign</button>
        </div>
      </article>

      <article v-if="canManage" class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Assigned documents</h2>
        <div v-if="loading" class="py-6 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="mt-3 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Employee</th>
                <th class="px-3 py-2">Document</th>
                <th class="px-3 py-2">Due</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2">Signed At</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in assignments" :key="`a-${item.id}`" class="border-t border-white/10">
                <td class="px-3 py-2">{{ item.employee?.name || '-' }}</td>
                <td class="px-3 py-2">
                  <p>{{ item.template?.title || '-' }}</p>
                  <button
                    v-if="item.template?.file_path"
                    class="mt-1 rounded-full border border-sky-300/40 bg-sky-300/10 px-2 py-1 text-[10px] uppercase tracking-[0.16em] text-sky-100"
                    @click="downloadTemplateFile(item.template)"
                  >
                    Download
                  </button>
                </td>
                <td class="px-3 py-2">{{ formatDate(item.due_date) }}</td>
                <td class="px-3 py-2 capitalize">{{ item.status }}</td>
                <td class="px-3 py-2">{{ formatDateTime(item.signed_at) }}</td>
              </tr>
              <tr v-if="!assignments.length">
                <td class="px-3 py-6 text-center text-sm text-slate-400" colspan="5">No assignments yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">My documents</h2>
        <div v-if="loading" class="py-6 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="mt-3 space-y-3">
          <div v-for="item in myAssignments" :key="`m-${item.id}`" class="rounded-xl border border-white/10 bg-slate-900/40 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="font-semibold">{{ item.template?.title || 'Untitled' }}</p>
                <p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-400">{{ item.template?.type || 'document' }}</p>
              </div>
              <p class="text-xs capitalize text-slate-300/80">Status: {{ item.status }}</p>
            </div>
            <p class="mt-3 whitespace-pre-wrap text-sm text-slate-200/90">{{ item.template?.content || '' }}</p>
            <button
              v-if="item.template?.file_path"
              class="mt-3 rounded-full border border-sky-300/40 bg-sky-300/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-100"
              @click="downloadTemplateFile(item.template)"
            >
              Download Document
            </button>
            <div class="mt-3 text-xs text-slate-400">Due: {{ formatDate(item.due_date, 'N/A') }}</div>

            <div v-if="canSign && item.status === 'pending' && item.template?.requires_signature" class="mt-3 flex flex-wrap items-center gap-2">
              <input
                v-model="signatureNames[item.id]"
                class="h-10 min-w-[220px] rounded-xl border border-white/10 bg-white/5 px-3 text-sm outline-none"
                placeholder="Type your full legal name"
              />
              <button
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200"
                @click="sign(item)"
              >
                Sign document
              </button>
            </div>
          </div>
          <div v-if="!myAssignments.length" class="py-6 text-center text-sm text-slate-400">No Drive documents assigned.</div>
        </div>
      </article>
    </div>
  </section>
</template>


