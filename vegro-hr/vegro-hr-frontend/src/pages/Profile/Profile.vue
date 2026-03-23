<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import authService from '../../services/authService';
import useAuth from '../../hooks/useAuth';
import { formatDate } from '../../utils/dateFormat';

defineOptions({ name: 'UserProfilePage' });

const { user, isLoading, error, checkAuth, roleTitle } = useAuth();

const isSaving = ref(false);
const saveError = ref('');
const saveSuccess = ref('');

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
});

watch(
  () => user.value,
  (value) => {
    form.value = {
      name: value?.name || '',
      email: value?.email || '',
      phone: value?.employee?.phone || value?.phone || '',
      password: '',
      password_confirmation: '',
    };
  },
  { immediate: true },
);

const displayName = computed(() => user.value?.name || user.value?.full_name || 'Unknown User');
const displayEmail = computed(() => user.value?.email || 'Not provided');
const displayRole = computed(() => {
  if (typeof user.value?.role === 'string') return user.value.role;
  if (user.value?.role?.title) return user.value.role.title;
  if (user.value?.role?.name) return user.value.role.name;
  return user.value?.role_name || 'Unassigned';
});
const displayDepartment = computed(() => {
  return (
    user.value?.department ||
    user.value?.department_name ||
    user.value?.employee?.department?.name ||
    'Not set'
  );
});
const displayPhone = computed(() => user.value?.employee?.phone || user.value?.phone || 'Not provided');
const displayJoined = computed(() => formatDate(user.value?.created_at || user.value?.createdAt, '—'));

const authStatus = computed(() => {
  if (isLoading.value) return 'Checking...';
  return user.value ? 'Authenticated' : 'Not authenticated';
});

const permissionBadges = computed(() => {
  const role = roleTitle.value;
  const map = {
    admin: [
      'Full access',
      'User management',
      'System settings',
      'Payroll oversight',
    ],
    hr: [
      'Employee management',
      'Attendance tracking',
      'Leave approvals',
      'Payroll access',
    ],
    finance: [
      'Payroll processing',
      'Payslip management',
      'Finance reports',
    ],
    manager: [
      'Department leaves',
      'Team approvals',
      'Leave insights',
    ],
    employee: [
      'My profile',
      'Leave requests',
      'Personal info',
    ],
  };

  return map[role] || ['Standard access'];
});

onMounted(() => {
  if (!user.value) {
    checkAuth();
  }
});

const saveProfile = async () => {
  isSaving.value = true;
  saveError.value = '';
  saveSuccess.value = '';

  try {
    const payload = {
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone || null,
    };

    if (form.value.password) {
      payload.password = form.value.password;
      payload.password_confirmation = form.value.password_confirmation;
    }

    await authService.updateCurrentUser(payload);
    await checkAuth();
    form.value.password = '';
    form.value.password_confirmation = '';
    saveSuccess.value = 'Profile updated.';
  } catch (err) {
    saveError.value = err?.response?.data?.message || 'Unable to update profile.';
  } finally {
    isSaving.value = false;
  }
};
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

      <div
        v-if="saveError"
        class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
      >
        {{ saveError }}
      </div>

      <div
        v-if="saveSuccess"
        class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100"
      >
        {{ saveSuccess }}
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

          <div class="mt-8 rounded-3xl border border-white/10 bg-slate-950/40 p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div>
                <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Self-service</p>
                <h3 class="mt-2 text-lg font-semibold">Update your profile</h3>
                <p class="mt-1 text-xs text-slate-400/80">
                  Update your contact details (and optionally your password).
                </p>
              </div>
              <button
                class="rounded-full border border-emerald-300/40 bg-emerald-300/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200 transition hover:bg-emerald-300/20 disabled:opacity-60"
                type="button"
                :disabled="isSaving"
                @click="saveProfile"
              >
                {{ isSaving ? 'Saving...' : 'Save' }}
              </button>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
              <label class="block text-xs text-slate-300/80 sm:col-span-2">
                <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Name</span>
                <input
                  v-model="form.name"
                  type="text"
                  class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                  autocomplete="name"
                />
              </label>

              <label class="block text-xs text-slate-300/80">
                <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Email</span>
                <input
                  v-model="form.email"
                  type="email"
                  class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                  autocomplete="email"
                />
              </label>

              <label class="block text-xs text-slate-300/80">
                <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Phone</span>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                  autocomplete="tel"
                />
              </label>

              <label class="block text-xs text-slate-300/80">
                <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">New password</span>
                <input
                  v-model="form.password"
                  type="password"
                  class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                  autocomplete="new-password"
                />
              </label>

              <label class="block text-xs text-slate-300/80">
                <span class="mb-2 block text-[11px] uppercase tracking-[0.2em] text-slate-400">Confirm password</span>
                <input
                  v-model="form.password_confirmation"
                  type="password"
                  class="h-11 w-full rounded-2xl border border-white/10 bg-white/5 px-4 text-sm text-slate-100 outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
                  autocomplete="new-password"
                />
              </label>
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
            <div class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="badge in permissionBadges"
                :key="badge"
                class="rounded-full border border-emerald-300/30 bg-emerald-300/10 px-3 py-1 text-xs text-emerald-200"
              >
                {{ badge }}
              </span>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>



