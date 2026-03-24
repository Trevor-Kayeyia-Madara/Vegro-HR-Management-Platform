<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import authService from '../../services/AuthService';

defineOptions({ name: 'ResetPasswordPage' });

const route = useRoute();
const router = useRouter();

const token = computed(() => String(route.query.token || ''));
const email = ref(String(route.query.email || ''));
const password = ref('');
const passwordConfirmation = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const submit = async () => {
  errorMessage.value = '';
  successMessage.value = '';
  isSubmitting.value = true;

  try {
    await authService.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    successMessage.value = 'Password reset successful. Redirecting to login...';
    setTimeout(() => router.push('/login'), 1200);
  } catch (error) {
    errorMessage.value = error?.response?.data?.message || 'Unable to reset password.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <div class="mx-auto flex min-h-screen w-full max-w-md items-center px-6 py-12">
      <form
        class="w-full rounded-3xl border border-white/10 bg-white/10 p-8 shadow-[0_25px_80px_rgba(15,23,42,0.55)] backdrop-blur"
        @submit.prevent="submit"
      >
        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-emerald-200/80">Account Recovery</p>
        <h1 class="mt-3 text-2xl font-semibold">Reset password</h1>
        <p class="mt-2 text-sm text-slate-200/80">
          Set a new password for your account.
        </p>

        <label class="mt-6 flex flex-col gap-2 text-sm text-slate-200/80">
          <span>Email</span>
          <input
            v-model="email"
            type="email"
            required
            class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            placeholder="you@company.com"
          />
        </label>

        <label class="mt-4 flex flex-col gap-2 text-sm text-slate-200/80">
          <span>New password</span>
          <input
            v-model="password"
            type="password"
            minlength="8"
            required
            class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            placeholder="********"
          />
        </label>

        <label class="mt-4 flex flex-col gap-2 text-sm text-slate-200/80">
          <span>Confirm password</span>
          <input
            v-model="passwordConfirmation"
            type="password"
            minlength="8"
            required
            class="h-12 rounded-xl border border-white/10 bg-slate-950/40 px-4 text-sm text-white outline-none transition focus:border-emerald-300/70 focus:ring-2 focus:ring-emerald-300/40"
            placeholder="********"
          />
        </label>

        <p
          v-if="errorMessage"
          class="mt-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
        >
          {{ errorMessage }}
        </p>
        <p
          v-if="successMessage"
          class="mt-4 rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100"
        >
          {{ successMessage }}
        </p>

        <button
          class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-xl bg-emerald-400 text-sm font-semibold text-slate-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-70"
          type="submit"
          :disabled="isSubmitting || !token"
        >
          {{ isSubmitting ? 'Resetting...' : 'Reset password' }}
        </button>

        <p v-if="!token" class="mt-3 text-xs text-amber-200/90">
          Missing reset token. Please open the link from your email.
        </p>

        <RouterLink to="/login" class="mt-4 inline-flex text-sm text-emerald-200 hover:text-emerald-100">
          Back to login
        </RouterLink>
      </form>
    </div>
  </div>
</template>

