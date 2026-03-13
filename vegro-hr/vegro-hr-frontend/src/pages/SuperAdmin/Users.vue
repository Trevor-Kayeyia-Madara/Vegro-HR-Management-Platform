<script setup>
import { computed, onMounted, ref } from 'vue';
import apiClient from '../../api/apiClient';

defineOptions({ name: 'SuperAdminUsersPage' });

const isLoading = ref(true);
const errorMessage = ref('');
const users = ref([]);

const securitySettings = ref({
  enforceMfa: true,
  sessionTimeoutHours: 8,
  allowedDomains: 'vegrohr.com, invodtechltd.com',
});

const normalizeRole = (title) =>
  String(title || '')
    .trim()
    .toLowerCase()
    .replace(/[\s_-]/g, '');

const systemAdmins = computed(() =>
  users.value.filter((user) => normalizeRole(user.role?.title) === 'superadmin'),
);

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString();
};

const loadUsers = async () => {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const response = await apiClient.get('/api/users', { params: { per_page: 200 } });
    const data = response?.data?.data;
    users.value = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load system users.';
  } finally {
    isLoading.value = false;
  }
};

onMounted(loadUsers);
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
      <section class="space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">
          Super Admin
        </p>
        <h1 class="text-3xl font-semibold sm:text-4xl">System Users</h1>
        <p class="max-w-2xl text-sm text-slate-300/70 sm:text-base">
          Manage super admin access, security settings, and global system policies.
        </p>
      </section>

      <p
        v-if="errorMessage"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ errorMessage }}
      </p>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">System admins</p>
            <h2 class="mt-2 text-lg font-semibold">Access control</h2>
          </div>
          <div class="flex items-center gap-3">
            <button
              class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs transition hover:bg-white/10"
              type="button"
              @click="loadUsers"
            >
              Refresh
            </button>
            <button
              class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200"
              type="button"
              disabled
            >
              Invite admin
            </button>
          </div>
        </div>
        <div class="mt-5 overflow-x-auto">
          <table class="min-w-full text-left text-xs text-slate-200">
            <thead class="text-[11px] uppercase text-slate-400">
              <tr>
                <th class="px-3 py-2">Name</th>
                <th class="px-3 py-2">Email</th>
                <th class="px-3 py-2">Role</th>
                <th class="px-3 py-2">Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in systemAdmins" :key="user.id" class="border-t border-white/5">
                <td class="px-3 py-3 font-semibold">{{ user.name }}</td>
                <td class="px-3 py-3">{{ user.email }}</td>
                <td class="px-3 py-3">
                  <span class="rounded-full bg-emerald-400/10 px-2 py-1 text-[10px] text-emerald-200">
                    {{ user.role?.title || 'Super Admin' }}
                  </span>
                </td>
                <td class="px-3 py-3">{{ formatDate(user.created_at) }}</td>
              </tr>
              <tr v-if="!isLoading && !systemAdmins.length">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="4">
                  No system admins found.
                </td>
              </tr>
              <tr v-if="isLoading">
                <td class="px-3 py-4 text-sm text-slate-400" colspan="4">
                  Loading system admins...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Security</p>
        <h2 class="mt-2 text-lg font-semibold">System settings</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2">
          <label class="flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm">
            <input v-model="securitySettings.enforceMfa" type="checkbox" class="h-4 w-4 accent-emerald-400" />
            Enforce MFA for all system admins
          </label>
          <label class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm">
            <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Session timeout</span>
            <input
              v-model="securitySettings.sessionTimeoutHours"
              type="number"
              min="1"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
            />
          </label>
          <label class="flex flex-col gap-2 rounded-2xl border border-white/10 bg-slate-950/40 px-4 py-3 text-sm sm:col-span-2">
            <span class="text-xs uppercase tracking-[0.24em] text-slate-400">Allowed email domains</span>
            <input
              v-model="securitySettings.allowedDomains"
              class="h-10 rounded-xl border border-white/10 bg-slate-950/40 px-3 text-sm outline-none"
            />
          </label>
        </div>
        <button
          class="mt-4 rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200"
          type="button"
          disabled
        >
          Save settings
        </button>
      </section>
    </div>
  </div>
</template>
