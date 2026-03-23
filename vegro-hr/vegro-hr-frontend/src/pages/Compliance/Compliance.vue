<script setup>
import { onMounted, ref } from 'vue';
import complianceService from '../../services/complianceService';
import useAuth from '../../hooks/useAuth';
import { formatDateTime } from '../../utils/dateFormat';

defineOptions({ name: 'CompliancePage' });

const { hasPermission } = useAuth();
const canManage = hasPermission('compliance.manage');

const loading = ref(false);
const scanning = ref(false);
const error = ref('');
const success = ref('');
const alerts = ref([]);

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await complianceService.getAlerts({ per_page: 100 });
    alerts.value = complianceService.parsePaginated(response).items;
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to load compliance alerts.';
  } finally {
    loading.value = false;
  }
};

const scan = async () => {
  scanning.value = true;
  error.value = '';
  success.value = '';
  try {
    const response = await complianceService.runScan();
    const payload = complianceService.unwrapPayload(response) || {};
    success.value = `Compliance scan complete. Alerts created: ${payload.alerts_created ?? 0}`;
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Compliance scan failed.';
  } finally {
    scanning.value = false;
  }
};

const acknowledge = async (alert) => {
  error.value = '';
  success.value = '';
  try {
    await complianceService.acknowledge(alert.id);
    success.value = 'Alert acknowledged.';
    await load();
  } catch (err) {
    error.value = err?.response?.data?.message || 'Failed to acknowledge alert.';
  }
};

const severityClass = (severity) => {
  const key = String(severity || '').toLowerCase();
  if (key === 'high') return 'border-rose-400/40 bg-rose-400/10 text-rose-200';
  if (key === 'medium') return 'border-amber-400/40 bg-amber-400/10 text-amber-200';
  return 'border-sky-400/40 bg-sky-400/10 text-sky-200';
};

onMounted(load);
</script>

<template>
  <section class="min-h-full bg-slate-950 px-4 py-6 text-white sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
      <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold sm:text-3xl">Compliance Alerts</h1>
          <p class="text-sm text-slate-300/80">Payroll, statutory, and labor-compliance monitoring.</p>
        </div>
        <button
          v-if="canManage"
          class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
          :disabled="scanning"
          @click="scan"
        >
          {{ scanning ? 'Scanning...' : 'Run Compliance Scan' }}
        </button>
      </header>

      <p v-if="error" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">{{ error }}</p>
      <p v-if="success" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ success }}</p>

      <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
        <div v-if="loading" class="py-8 text-center text-sm text-slate-400">Loading...</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-[0.2em] text-slate-400">
              <tr>
                <th class="px-3 py-2">Severity</th>
                <th class="px-3 py-2">Title</th>
                <th class="px-3 py-2">Detected</th>
                <th class="px-3 py-2">Status</th>
                <th class="px-3 py-2 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="alert in alerts" :key="alert.id" class="border-t border-white/10">
                <td class="px-3 py-2">
                  <span class="inline-flex rounded-full border px-2 py-1 text-[10px] uppercase tracking-[0.16em]" :class="severityClass(alert.severity)">
                    {{ alert.severity }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <p class="font-medium">{{ alert.title }}</p>
                  <p class="text-xs text-slate-400">{{ alert.message }}</p>
                </td>
                <td class="px-3 py-2 text-slate-300/80">{{ formatDateTime(alert.detected_at) }}</td>
                <td class="px-3 py-2">
                  <span
                    class="inline-flex rounded-full border px-2 py-1 text-[10px] uppercase tracking-[0.16em]"
                    :class="alert.acknowledged_at ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200' : 'border-amber-400/40 bg-amber-400/10 text-amber-200'"
                  >
                    {{ alert.acknowledged_at ? 'Acknowledged' : 'Open' }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right">
                  <button
                    v-if="canManage && !alert.acknowledged_at"
                    class="rounded-full border border-sky-400/30 bg-sky-400/10 px-3 py-1 text-xs text-sky-200 transition hover:bg-sky-400/20"
                    @click="acknowledge(alert)"
                  >
                    Acknowledge
                  </button>
                </td>
              </tr>
              <tr v-if="!alerts.length">
                <td class="px-3 py-6 text-center text-sm text-slate-400" colspan="5">No compliance alerts.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>

