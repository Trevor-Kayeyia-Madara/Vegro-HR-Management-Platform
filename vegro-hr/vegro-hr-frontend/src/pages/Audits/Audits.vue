<script setup>
import { onMounted, ref } from 'vue';
import auditService from '../../services/auditService';
import { formatDateTime } from '../../utils/dateFormat';

defineOptions({ name: 'AuditsPage' });

const loading = ref(false);
const error = ref('');
const items = ref([]);

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await auditService.getModelChanges({ per_page: 100 });
    items.value = auditService.parsePaginated(response).items;
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load audits.';
  } finally {
    loading.value = false;
  }
};

const summarize = (value) => {
  if (!value || typeof value !== 'object') return '-';
  const keys = Object.keys(value);
  if (!keys.length) return '-';
  return keys.slice(0, 6).join(', ');
};

const shortEntity = (entityType) => {
  const value = String(entityType || '');
  if (!value.includes('\\')) return value;
  return value.split('\\').pop();
};

const actionClass = (action) => {
  const key = String(action || '').toLowerCase();
  if (key === 'created') return 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200';
  if (key === 'updated') return 'border-sky-400/40 bg-sky-400/10 text-sky-200';
  if (key === 'deleted') return 'border-rose-400/40 bg-rose-400/10 text-rose-200';
  return 'border-white/20 bg-white/10 text-slate-200';
};

onMounted(load);
</script>

<template>
  <section class="min-h-full bg-slate-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold sm:text-3xl">Detailed Audits</h1>
          <p class="text-sm text-slate-300/80">Who changed what and when across HR modules.</p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          @click="load"
        >
          Refresh
        </button>
      </header>

      <p v-if="error" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">{{ error }}</p>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Time</th>
                <th class="px-3 py-2">Actor</th>
                <th class="px-3 py-2">Entity</th>
                <th class="px-3 py-2">Action</th>
                <th class="px-3 py-2">Changed Fields</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in items" :key="item.id" class="border-t border-white/10">
                <td class="px-3 py-2 text-slate-300/80">{{ formatDateTime(item.created_at) }}</td>
                <td class="px-3 py-2 text-slate-300/80">{{ item.actor?.name || 'System' }}</td>
                <td class="px-3 py-2 text-slate-300/80">{{ shortEntity(item.entity_type) }} #{{ item.entity_id }}</td>
                <td class="px-3 py-2">
                  <span class="inline-flex rounded-full border px-2 py-1 text-[10px] uppercase tracking-[0.16em]" :class="actionClass(item.action)">
                    {{ item.action }}
                  </span>
                </td>
                <td class="px-3 py-2 text-xs text-slate-400">{{ summarize(item.after_data || item.before_data) }}</td>
              </tr>
              <tr v-if="!items.length">
                <td class="px-3 py-6 text-center text-sm text-slate-400" colspan="5">No audit data yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>

