<script setup>
import { computed, onMounted } from 'vue';
import useAuth from '../../hooks/useAuth';

defineOptions({ name: 'UserProfilePage' });

const { user, isLoading, error, checkAuth } = useAuth();

const displayName = computed(() => user.value?.name || user.value?.full_name || 'Unknown User');
const displayEmail = computed(() => user.value?.email || 'Not provided');
const displayRole = computed(() => {
  if (typeof user.value?.role === 'string') return user.value.role;
  if (user.value?.role?.title) return user.value.role.title;
  if (user.value?.role?.name) return user.value.role.name;
  return user.value?.role_name || 'Unassigned';
});
const displayDepartment = computed(() => user.value?.department || user.value?.department_name || 'Not set');
const displayPhone = computed(() => user.value?.phone || 'Not provided');
const displayJoined = computed(() => user.value?.created_at || user.value?.createdAt || '—');

const authStatus = computed(() => {
  if (isLoading.value) return 'Checking...';
  return user.value ? 'Authenticated' : 'Not authenticated';
});

onMounted(() => {
  if (!user.value) {
    checkAuth();
  }
});
</script>

<template>
  <div class="min-h-full bg-slate-950 text-white">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Profile</p>
          <h1 class="text-3xl font-semibold">Account Details</h1>
          <p class="mt-2 text-sm text-slate-300/70">
            View the authenticated user profile pulled from your API.
          </p>
        </div>
        <button
          class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-200 transition hover:bg-white/10"
          type="button"
          @click="checkAuth"
        >
          Refresh
        </button>
      </div>

      <div
        v-if="error"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ error }}
      </div>

      <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <section class="rounded-3xl border border-white/10 bg-white/5 p-8">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/20 text-xl font-semibold text-emerald-200">
              {{ displayName.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h2 class="text-xl font-semibold">{{ displayName }}</h2>
              <p class="text-sm text-slate-300/70">{{ displayEmail }}</p>
            </div>
          </div>

          <div class="mt-8 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Role</p>
              <p class="mt-2 text-lg font-semibold">{{ displayRole }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Department</p>
              <p class="mt-2 text-lg font-semibold">{{ displayDepartment }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Phone</p>
              <p class="mt-2 text-lg font-semibold">{{ displayPhone }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4">
              <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Joined</p>
              <p class="mt-2 text-lg font-semibold">{{ displayJoined }}</p>
            </div>
          </div>
        </section>

        <aside class="flex flex-col gap-4 rounded-3xl border border-white/10 bg-white/5 p-6">
          <h3 class="text-lg font-semibold">Session Status</h3>
          <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Authentication</p>
            <p
              class="mt-2 font-semibold"
              :class="user ? 'text-emerald-200' : 'text-amber-200'"
            >
              {{ authStatus }}
            </p>
            <p class="mt-2 text-xs text-slate-400/80">
              Keep your session secure. Log out from the sidebar when you are done.
            </p>
          </div>
          <div class="rounded-2xl border border-white/10 bg-slate-950/40 p-4 text-sm">
            <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Access Level</p>
            <p class="mt-2 font-semibold text-slate-100">{{ displayRole }}</p>
            <p class="mt-2 text-xs text-slate-400/80">
              Permissions are determined by your role assignment in Vegro HR.
            </p>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

