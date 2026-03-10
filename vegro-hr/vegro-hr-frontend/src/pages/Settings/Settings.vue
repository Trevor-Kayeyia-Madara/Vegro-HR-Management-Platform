<script setup>
import { computed, onMounted, ref } from 'vue';

defineOptions({ name: 'AdminSettingsPage' });

const STORAGE_KEY = 'vegro_hr_settings';
const status = ref('');

const form = ref({
  organization_name: '',
  default_currency: 'USD',
  timezone: 'UTC',
  payroll_cycle: 'monthly',
  notifications_enabled: true,
});

const loadSettings = () => {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    form.value = {
      organization_name: saved.organization_name || '',
      default_currency: saved.default_currency || 'USD',
      timezone: saved.timezone || 'UTC',
      payroll_cycle: saved.payroll_cycle || 'monthly',
      notifications_enabled: saved.notifications_enabled ?? true,
    };
  } catch {
    // ignore malformed storage
  }
};

const saveSettings = () => {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(form.value));
  status.value = 'Settings saved.';
  setTimeout(() => {
    status.value = '';
  }, 2000);
};

const summary = computed(() => [
  { label: 'Organization', value: form.value.organization_name || 'Not set' },
  { label: 'Currency', value: form.value.default_currency },
  { label: 'Timezone', value: form.value.timezone },
  { label: 'Payroll cycle', value: form.value.payroll_cycle },
]);

onMounted(loadSettings);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Settings</p>
          <h1 class="text-3xl font-semibold">Admin Settings</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            Configure organization-level defaults for Vegro HR.
          </p>
        </div>
        <button
          class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20"
          type="button"
          @click="saveSettings"
        >
          Save settings
        </button>
      </div>

      <p
        v-if="status"
        class="rounded-2xl border border-emerald-300/30 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-100"
      >
        {{ status }}
      </p>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <form class="rounded-3xl border border-white/10 bg-white/5 p-6" @submit.prevent="saveSettings">
          <div class="grid gap-4 sm:grid-cols-2">
            <label class="flex flex-col gap-2 text-sm text-slate-200/80 sm:col-span-2">
              <span>Organization name</span>
              <input
                v-model="form.organization_name"
                type="text"
                placeholder="Vegro HR"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Default currency</span>
              <input
                v-model="form.default_currency"
                type="text"
                maxlength="3"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white uppercase outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Timezone</span>
              <input
                v-model="form.timezone"
                type="text"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              />
            </label>
            <label class="flex flex-col gap-2 text-sm text-slate-200/80">
              <span>Payroll cycle</span>
              <select
                v-model="form.payroll_cycle"
                class="h-11 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
              >
                <option value="monthly">Monthly</option>
                <option value="biweekly">Biweekly</option>
                <option value="weekly">Weekly</option>
              </select>
            </label>
            <label class="flex items-center gap-3 text-sm text-slate-200/80">
              <input
                v-model="form.notifications_enabled"
                type="checkbox"
                class="h-4 w-4 rounded border-white/10 bg-slate-950/40 text-emerald-300 focus:ring-emerald-300"
              />
              <span>Enable email notifications</span>
            </label>
          </div>

          <button
            class="mt-6 inline-flex h-11 items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300"
            type="submit"
          >
            Save settings
          </button>
        </form>

        <aside class="rounded-3xl border border-white/10 bg-white/5 p-6">
          <h2 class="text-lg font-semibold">Current Defaults</h2>
          <div class="mt-4 flex flex-col gap-3">
            <div
              v-for="item in summary"
              :key="item.label"
              class="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm"
            >
              <span class="text-slate-300/70">{{ item.label }}</span>
              <span class="font-semibold text-slate-100">{{ item.value }}</span>
            </div>
          </div>
          <p class="mt-6 text-xs text-slate-400/80">
            These defaults are stored locally for now. Connect to backend settings later if needed.
          </p>
        </aside>
      </div>
    </div>
  </div>
</template>
