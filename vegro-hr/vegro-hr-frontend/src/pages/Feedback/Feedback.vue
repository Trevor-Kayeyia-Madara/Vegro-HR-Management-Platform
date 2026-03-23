<script setup>
import { computed, onMounted, ref } from 'vue';
import useAuth from '../../hooks/useAuth';
import feedbackService from '../../services/feedbackService';
import { formatDate } from '../../utils/dateFormat';

defineOptions({ name: 'FeedbackPage' });

const { hasPermission } = useAuth();

const canViewAll = computed(() => hasPermission('feedback.view'));
const canManage = computed(() => hasPermission('feedback.manage'));

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');
const items = ref([]);

const form = ref({
  category: 'general',
  subject: '',
  message: '',
});

const categories = [
  { value: 'general', label: 'General' },
  { value: 'payroll', label: 'Payroll' },
  { value: 'leaves', label: 'Leaves' },
  { value: 'manager', label: 'Manager' },
  { value: 'workplace', label: 'Workplace' },
  { value: 'other', label: 'Other' },
];

const statuses = ['open', 'in_review', 'resolved', 'closed'];

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = canViewAll.value
      ? await feedbackService.getAll({ per_page: 50 })
      : await feedbackService.getMine({ per_page: 50 });
    items.value = feedbackService.parsePaginated(response).items;
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load feedback.';
  } finally {
    loading.value = false;
  }
};

const submit = async () => {
  if (!form.value.subject || !form.value.message) {
    error.value = 'Subject and message are required.';
    return;
  }

  saving.value = true;
  error.value = '';
  success.value = '';
  try {
    await feedbackService.create(form.value);
    form.value = {
      category: 'general',
      subject: '',
      message: '',
    };
    success.value = 'Feedback submitted successfully.';
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to submit feedback.';
  } finally {
    saving.value = false;
  }
};

const updateStatus = async (item, status) => {
  if (!canManage.value) return;
  try {
    await feedbackService.update(item.id, { status });
    item.status = status;
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to update feedback.';
  }
};

onMounted(load);
</script>

<template>
  <section class="min-h-full bg-slate-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <header class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold sm:text-3xl">Employee Feedback</h1>
        <p class="text-sm text-slate-300/80">
          Submit confidential feedback and track response progress.
        </p>
      </header>

      <p v-if="error" class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
        {{ error }}
      </p>
      <p v-if="success" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
        {{ success }}
      </p>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-4 sm:p-6">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">Submit feedback</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <label class="text-xs text-slate-300/80">
            <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Category</span>
            <select
              v-model="form.category"
              class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none"
            >
              <option v-for="option in categories" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>

          <label class="text-xs text-slate-300/80">
            <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Subject</span>
            <input
              v-model="form.subject"
              type="text"
              maxlength="255"
              class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none"
              placeholder="Brief subject"
            />
          </label>

          <label class="text-xs text-slate-300/80 sm:col-span-2">
            <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Message</span>
            <textarea
              v-model="form.message"
              rows="4"
              maxlength="5000"
              class="w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-100 outline-none"
              placeholder="Share details..."
            />
          </label>
        </div>

        <div class="mt-4 flex justify-end">
          <button
            class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
            type="button"
            :disabled="saving"
            @click="submit"
          >
            {{ saving ? 'Submitting...' : 'Submit' }}
          </button>
        </div>
      </article>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-200/80">
          {{ canViewAll ? 'Company feedback queue' : 'My feedback' }}
        </h2>
        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Date</th>
                <th class="px-3 py-2">Category</th>
                <th class="px-3 py-2">Subject</th>
                <th v-if="canViewAll" class="px-3 py-2">Submitted By</th>
                <th class="px-3 py-2">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="border-t border-white/10">
                <td class="px-3 py-2 text-slate-300/80">{{ formatDate(item.created_at) }}</td>
                <td class="px-3 py-2 capitalize">{{ item.category }}</td>
                <td class="px-3 py-2">
                  <p class="font-medium">{{ item.subject }}</p>
                  <p class="text-xs text-slate-400">{{ item.message }}</p>
                </td>
                <td v-if="canViewAll" class="px-3 py-2 text-slate-300/80">{{ item.submitter?.name || '-' }}</td>
                <td class="px-3 py-2">
                  <select
                    v-if="canManage"
                    :value="item.status"
                    class="h-9 rounded-xl border border-white/10 bg-slate-900/60 px-3 text-xs uppercase tracking-[0.2em]"
                    @change="updateStatus(item, $event.target.value)"
                  >
                    <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                  </select>
                  <span v-else class="capitalize">{{ item.status }}</span>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td class="px-3 py-6 text-center text-sm text-slate-400" :colspan="canViewAll ? 5 : 4">
                  No feedback yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>


